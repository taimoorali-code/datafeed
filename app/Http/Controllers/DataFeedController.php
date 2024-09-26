<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Routing\Controller as BaseController;

class DataFeedController extends Controller
{
    public function generateFeed($language)
    {
        // Assuming you have the JSON data stored in 'json_ar' field for Arabic and 'json_en' for English
        $products = Product::where('status', 'active')
            ->whereNotNull('json_ar') // Ensure json_ar is not null
            ->whereNotNull('json_en') // Ensure json_en is not null
            ->select('json_ar', 'json_en') // Select only the JSON fields
            ->get();


        // Initialize XML
        $xml = new \SimpleXMLElement('<rss version="2.0"/>');
        $xml->addAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', 'Noha Nabil Feed');
        $channel->addChild('link', 'https://nohanabil.com');
        $channel->addChild('description', 'Product feed for Noha Nabil store');

        // Loop through each product
        foreach ($products as $product) {
            // Get the language-specific data
            $productJson = $language === 'ar' ? $product->json_ar : $product->json_en;
            $productData = json_decode($productJson, true);


            if (isset($productData['product'])) {
                # code...
                // Loop through each variant in the product
                foreach ($productData['product']['variants'] as $variant) {
                    $item = $channel->addChild('item');
                    // Add fields to XML
                    $item->addChild('g:id', 'shopify_AE_' . $productData['product']['id'] . '_' . $variant['id']);
                    $item->addChild('g:item_group_id', $productData['product']['id'] ?? 'N/A');
                    $item->addChild('g:title', htmlspecialchars($productData['product']['title'] ?? 'No Title', ENT_XML1, 'UTF-8'));
                    // Get description from the body_html field and format it
                    $description = $this->formatDescription($productData['product']['body_html'] ?? 'No description available', $variant['title'], $language);
                    $item->addChild('g:description', htmlspecialchars($description, ENT_XML1, 'UTF-8'));

                    // Add other relevant fields from JSON
                    $item->addChild('g:link', 'https://nohanabil.com/products/' . ($productData['product']['handle'] ?? '') . '?variant=' . $variant['id']);
                    $item->addChild('g:image_link', $productData['product']['images'][0]['src'] ?? 'https://no-image.com');
                    $item->addChild('g:brand', htmlspecialchars($productData['product']['vendor'] ?? 'Noha Nabil', ENT_XML1, 'UTF-8'));
                    $item->addChild('g:price', ($variant['price'] ?? '0') . ' AED');
                    $item->addChild('g:condition', 'new');
                    $item->addChild('g:sell_on_google_quantity', $variant['inventory_quantity'] ?? '0');
                    $item->addChild('g:product_type', htmlspecialchars($productData['product']['product_type'] ?? 'Uncategorized', ENT_XML1, 'UTF-8'));
                    $item->addChild('g:shipping_weight', ($variant['weight'] ?? '0') . ' ' . ($variant['weight_unit'] ?? 'kg'));
                    $item->addChild('g:gtin', $variant['barcode'] ?? 'N/A');
                    $item->addChild('g:identifier_exists', $variant['barcode'] ? 'true' : 'false');
                    $item->addChild('g:availability', 'in stock');
                    $item->addChild('g:color', $variant['title'] ?? 'N/A');
                    $item->addChild('g:google_product_category', '6305');
                    $item->addChild('g:custom_label_0', 'Face');
                    $item->addChild('g:custom_label_1', $language === 'ar' ? 'AR' : 'EN');
                }
            } else {
                foreach ($productData['variants'] as $variant) {
                    $item = $channel->addChild('item');

                    // Add fields to XML
                    $item->addChild('g:id', 'shopify_AE_' . $productData['id'] . '_' . $variant['id']);
                    $item->addChild('g:item_group_id', $productData['id'] ?? 'N/A');
                    // no aed   
                    $item->addChild('g:title', htmlspecialchars($productData['title'] ?? 'No Title', ENT_XML1, 'UTF-8'));

                    // Get description from the body_html field and format it
                    $description = $this->formatDescription($productData['body_html'] ?? 'No description available', $variant['title'], $language);
                    $item->addChild('g:description', htmlspecialchars($description, ENT_XML1, 'UTF-8'));

                    // Add other relevant fields from JSON
                    $item->addChild('g:link', 'https://nohanabil.com/products/' . ($productData['handle'] ?? '') . '?variant=' . $variant['id']);
                    $item->addChild('g:image_link', $productData['images'][0]['src'] ?? 'https://no-image.com');
                    $item->addChild('g:brand', htmlspecialchars($productData['vendor'] ?? 'Noha Nabil', ENT_XML1, 'UTF-8'));
                    $item->addChild('g:price', ($variant['price'] ?? '0') . ' AED');
                    $item->addChild('g:condition', 'new');
                    $item->addChild('g:sell_on_google_quantity', $variant['inventory_quantity'] ?? '0');
                    $item->addChild('g:product_type', htmlspecialchars($productData['product_type'] ?? 'Uncategorized', ENT_XML1, 'UTF-8'));
                    $item->addChild('g:shipping_weight', ($variant['weight'] ?? '0') . ' ' . ($variant['weight_unit'] ?? 'kg'));
                    $item->addChild('g:gtin', $variant['barcode'] ?? 'N/A');
                    $item->addChild('g:identifier_exists', $variant['barcode'] ? 'true' : 'false');
                    $item->addChild('g:availability', 'in stock');
                    $item->addChild('g:color', $variant['title'] ?? 'N/A');
                    // $item->addChild('g:google_product_category', '6305');
                    // $item->addChild('g:custom_label_0', 'Face');
                    $item->addChild('g:custom_label_1', $language === 'ar' ? 'AR' : 'EN');
                }
            }
        }

        // Set file name
        $fileName = 'product_feed_' . $language . '.xml';

        // Return the XML response
        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    // Helper function to format the description
    private function formatDescription($bodyHtml, $color, $language)
    {
        // Strip unnecessary HTML tags and limit description length
        $cleanDescription = strip_tags($bodyHtml, '<br><strong><em>'); // Retain desired HTML tags

        // Format description based on language
        if ($language === 'ar') {
            $formattedDescription = $cleanDescription . ' لماذا ستحبه: ' .
                'خالي من التلك، والبارابين، والسيليكون، والزيوت المعدنية، والشمع، والخرز البلاستيكي. ' .
                'نباتي، وخالي من التجارب على الحيوانات، وخالي من الكائنات المعدلة وراثيًا، ومعتمد من RSPO. ' .
                'مكياج غني بالألوان، ومشبع بفيتامين روز، وخفيف الوزن، وقابل للتطبيق بشكل متدرج. ' .
                'صيغة طويلة الأمد. 3 درجات لتناسب جميع ألوان البشرة. ' .
                'اللون: ' . $color;
        } else {
            $formattedDescription = $cleanDescription . ' Why you will love it: ' .
                'Free of Talc, Parabens, Silicone, Mineral Oil, Waxes, and Plastic Beads. ' .
                'VEGAN, Cruelty-Free, GMO-Free, and RSPO CERTIFIED. ' .
                'Highly pigmented. Infused with Vita Rose. Lightweight and buildable. ' .
                'Long-lasting formula. 3 shades to suit all skin tones. ' .
                'Color: ' . $color;
        }

        return $formattedDescription;
    }
}
