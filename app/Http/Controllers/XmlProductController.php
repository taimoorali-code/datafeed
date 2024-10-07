<?php

namespace App\Http\Controllers;

use App\Models\Product;

class XmlProductController extends Controller
{
    public function generateXml($language)
    {
        // Fetch all active products
        $activeProducts = Product::where('status', 'active')->get();

        // Define the initial structure of the XML
        $xml = new \SimpleXMLElement('<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"></rss>');
        $channel = $xml->addChild('channel');

        // Loop through each product and generate XML nodes
        foreach ($activeProducts as $product) {
            // Determine the appropriate JSON based on the language context
            $productData = json_decode($product->json_en, true); // Default to English
            if ($language === 'sq') { // Check if language is 'sq'
                $productData = json_decode($product->json_sq, true);
            }
            $linkBase = ($language === 'sq') ? 'https://bonbona.al/products/' : 'https://bonbona.al/en/products/';
            // Ensure that title and body_html keys exist
            $title = isset($productData['title']) ? htmlspecialchars($productData['title']) : 'Untitled';
            $description = isset($productData['body_html']) ? htmlspecialchars(strip_tags($productData['body_html'])) : 'No description available';

            // Check if 'variants' key exists
            if (isset($productData['variants']) && !empty($productData['variants'])) {
                // Loop through product variants
                foreach ($productData['variants'] as $variant) {
                    $item = $channel->addChild('item');

                    $item->addChild('id', $variant['id']);
                    $item->addChild('override', 'sq_AL');
                    $item->addChild('title', $productData['title']);
                    $item->addChild('description', $description);
                    // $item->addChild('link', 'https://' . env('SHOPIFY_STORE_URL') . '/products/' . $productData['handle']);
                    $item->addChild('link', $linkBase . $productData['handle']);

                    // Retrieve color value from options
                    $color = ''; // Default value for color
                    if (isset($productData['options'])) {
                        foreach ($productData['options'] as $option) {
                            if (strtolower($option['name']) === 'color' && !empty($option['values'])) {
                                $color = $option['values'][0]; // Get the first value for color
                                break; // No need to continue looping through options
                            }
                        }
                    }
                    $item->addChild('color', htmlspecialchars($color)); // Add color to XML

                    // Retrieve material value from options
                    $material = ''; // Default value for material
                    if (isset($productData['options'])) {
                        foreach ($productData['options'] as $option) {
                            if (strtolower($option['name']) === 'material' && !empty($option['values'])) {
                                $material = $option['values'][0]; // Get the first value for material
                                break; // No need to continue looping through options
                            }
                        }
                    }
                    $item->addChild('material', htmlspecialchars($material)); // Add material to XML

                    // $item->addChild('pattern', '');   // Leave empty for now as per your example
                }
            } else {
                if (isset($productData['product'])) {
                    foreach ($productData['product']['variants'] as $variant) {
                        $item = $channel->addChild('item');
                        $item->addChild('id', $variant['id']);
                        $item->addChild('override', 'sq_AL');
                        $item->addChild('title', $productData['product']['title']);
                        $description = isset($productData['product']['body_html']) ? htmlspecialchars(strip_tags($productData['product']['body_html'])) : 'No description available';
                        $item->addChild('description', $description);
                        // $item->addChild('link', 'https://' . env('SHOPIFY_STORE_URL') . '/products/' . $productData['product']['handle']);
                        $item->addChild('link', $linkBase . $productData['product']['handle']);

                        // Retrieve color value from options
                        $color = ''; // Default value for color
                        if (isset($productData['product']['options'])) {
                            foreach ($productData['product']['options'] as $option) {
                                if (strtolower($option['name']) === 'ngjyra' && !empty($option['values'])) {
                                    $color = $option['values'][0]; // Get the first value for color
                                    break; // No need to continue looping through options
                                }
                            }
                        }
                        $item->addChild('color', htmlspecialchars($color)); // Add color to XML

                        // Retrieve material value from options
                        $material = ''; // Default value for material
                        if (isset($productData['product']['options'])) {
                            foreach ($productData['product']['options'] as $option) {
                                if (strtolower($option['name']) === 'materiali' && !empty($option['values'])) {
                                    $material = $option['values'][0]; // Get the first value for material
                                    break; // No need to continue looping through options
                                }
                            }
                        }
                        $item->addChild('material', htmlspecialchars($material)); // Add material to XML

                        // $item->addChild('pattern', '');   // Leave empty for now as per your example
                    }
                }
            }
        }

        // Output the XML content after all products have been processed
        $xmlOutput = $xml->asXML();

        // Prepare file for download
        $fileName = $language === 'sq' ? 'language_feed_template_sq_AL.xml' : 'language_feed_template_en.xml'; // Change filename based on language
        return response($xmlOutput)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
