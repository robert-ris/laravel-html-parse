<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;

class ParseHtmlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'html-parse {folderPath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse html content and save it to database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $folderPath = $this->argument('folderPath');

        // Get all HTML files in the folder
        $files = glob($folderPath . '/*.html');


        foreach ($files as $file) {
            $html = file_get_contents($file);

            $dom = new DOMDocument;
            @$dom->loadHTML($html);

            $xpath = new DOMXPath($dom);

            // Query all div tags
            $divTags = $xpath->query('//div');

            $data = [];
            foreach ($divTags as $divTag) {

                $words = ['Property Type', 'Company Type', 'Exchange', 'Ticker', 'Listing Status', '1 Year Return', '2 year return', '3 year return', '5 year return', '10 year return', 'Market Cap', 'Dividend Yield', 'Dividend Amount', 'Ex-Dividend Date'];
                foreach ($words as $word) {
                    if (strpos($divTag->nodeValue, $word) !== false) {
                        // Get the next div tag
                        $nextDivTag = $divTag->nextSibling;

                        while ($nextDivTag && $nextDivTag->nodeName !== 'div') {
                            $nextDivTag = $nextDivTag->nextSibling;
                        }

                        if ($nextDivTag) {
                            // Create an object with the value of the next tag
                            $name = $xpath->query('//span[@class="field field--name-title field--type-string field--label-hidden"]')->item(0);
                            $description = $xpath->query('//div[@class="clearfix text-formatted field field--name-field-about-the-company field--type-text-with-summary field--label-hidden field__item"]')->item(0);
                            $price = $xpath->query('//div[@class="company-highlights--right-close"]')->item(0);
                            $filename = basename($file);
                            $snakeCaseWord = strtolower(str_replace(' ', '_', $word));
                            $data["title"] = $name->nodeValue;
                            $data["file_name"] = $filename;
                            $data['description'] = $description->nodeValue;
                            $data['price'] = trim(str_replace("\n", "", $price->nodeValue));
                            $data[$snakeCaseWord] = trim(str_replace("\n", "", $nextDivTag->nodeValue));

                        }
                    }
                }
            }



            foreach ($data as $key => $value) {

            // print_r($data['file_name'] . "\n");

            // Load the XML
            $dom = new DOMDocument;

            // if ($value == 'acadia-realty-trust﹖destination=ꤷinvestingꤷreit-directoryꤷacadia-realty-trust.html') {
            //     $fileName = '/Users/robertisacescu/Downloads/reitsguide.WordPress.2024-04-26.xml';
            // } else {
            //     $fileName = '/Users/robertisacescu/Documents/reits/' .  $data['file_name'];
            // }

            $fileName = '/Users/robertisacescu/Downloads/reitsguide.WordPress.2024-04-26.xml';

            $dom->load($fileName);

            // Create a new XPath object
            $xpath = new DOMXPath($dom);

            // Find the rss and channel elements
            $rssElements = $xpath->query('//rss');
            $channelElements = $xpath->query('//channel');

            if ($rssElements->length > 0 && $channelElements->length > 0) {
                // Get the first rss and channel elements
                $rssElement = $rssElements->item(0);
                $channelElement = $channelElements->item(0);


                // Create a new item element for each data
                $item = $dom->createElement('item');

                // Populate the item element with data from $data

                    if ($key == 'description' && empty($value)) {
                        throw new \Exception('Description is empty');
                    }

                    if ($key == 'title') {
                        $title = $dom->createElement('title');
                        $title->appendChild($dom->createCDATASection($value));
                        $item->appendChild($title);
                        continue; // Skip the rest of the loop for the title key
                    }

                    // Create a new wp:postmeta element
                    $postmeta = $dom->createElement('wp:postmeta');

                    // Create a new wp:meta_key element with the key as the text content
                    $meta_key = $dom->createElement('wp:meta_key');
                    $meta_key->appendChild($dom->createCDATASection($key));

                    // Create a new wp:meta_value element with the value as the text content
                    $meta_value = $dom->createElement('wp:meta_value');
                    $meta_value->appendChild($dom->createCDATASection($value));

                    // Append the wp:meta_key and wp:meta_value elements to the wp:postmeta element
                    $postmeta->appendChild($meta_key);
                    $postmeta->appendChild($meta_value);

                    // Append the wp:postmeta element to the item element instead of the root element
                    $item->appendChild($postmeta);

                    // Create a new wp:postmeta element
                    $postmeta = $dom->createElement('wp:postmeta');

                    // Create a new wp:meta_key element with 'hp_parent_model' as the text content
                    $meta_key = $dom->createElement('wp:meta_key');
                    $meta_key->appendChild($dom->createCDATASection('hp_parent_model'));

                    // Create a new wp:meta_value element with 'listing' as the text content
                    $meta_value = $dom->createElement('wp:meta_value');
                    $meta_value->appendChild($dom->createCDATASection('listing'));

                    // Append the wp:meta_key and wp:meta_value elements to the wp:postmeta element
                    $postmeta->appendChild($meta_key);
                    $postmeta->appendChild($meta_value);

                    // Append the wp:postmeta element to the item element
                    $item->appendChild($postmeta);

                    // Create a new wp:post_type element with 'hp_listing' as the text content
                    $post_type = $dom->createElement('wp:post_type');
                    $post_type->appendChild($dom->createCDATASection('hp_listing'));

                    // Append the wp:post_type element to the item element
                    $item->appendChild($post_type);

                    // Append the item element to the channel element
                    $channelElement->appendChild($item);
                    // Save the XML as a string
                    $xmlString = $dom->saveXML();

                     // Print the XML string
                    // echo $xmlString;

                    $dom->save('/Users/robertisacescu/Downloads/reitsguide.WordPress.2024-04-26.xml');
                }
            }

        }

        // print_r($allData);
    }
}
