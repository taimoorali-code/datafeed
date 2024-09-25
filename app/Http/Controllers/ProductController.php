<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function syncProducts()
    {
        $client = new Client([
            'base_uri' => 'https://' . env('SHOPIFY_STORE_URL') . '/admin/api/2023-04/',
            'headers' => [
                'X-Shopify-Access-Token' => env('SHOPIFY_ACCESS_TOKEN'),
            ],
            'verify' => false,
        ]);

        $hasNextPage = true;
        $pageInfo = null; // For storing the `page_info` for the next request

        try {
            while ($hasNextPage) {
                $response = $client->get('products.json', [
                    'query' => [
                        'limit' => 250,  // Shopify API allows a maximum of 250 items per page
                        'page_info' => $pageInfo,
                    ]
                ]);
                $data = json_decode($response->getBody(), true);
                $products = $data['products'];

                foreach ($products as $shopifyProduct) {
                    // Store English product JSON in `json_en`
                    $product = Product::updateOrCreate(
                        ['shopify_product_id' => $shopifyProduct['id']],
                        [
                            'shopify_product_id' => $shopifyProduct['id'],
                            'status' => $shopifyProduct['status'],
                            'json_en' => json_encode($shopifyProduct), // English JSON
                        ]
                    );

                    // Fetch and store Arabic product JSON
                    $this->syncArabicProduct($client, $shopifyProduct['handle'], $product->shopify_product_id);
                }

                // Check if there is another page
                $links = $response->getHeader('Link');
                $hasNextPage = $this->checkForNextPage($links);
                $pageInfo = $this->getNextPageInfo($links);
            }

            return response()->json(['success' => true, 'message' => 'Products synced successfully!'], 200);
        } catch (ConnectException $e) {
            // Handle connection timeout or other network-related errors
            Log::error("Connection error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch products due to connection error. Please try again later.'], 504);
        } catch (RequestException $e) {
            // Handle other request exceptions
            $errorMessage = $e->getResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            Log::error("Request error: " . $errorMessage);
            return response()->json(['error' => 'Failed to fetch products. Error: ' . $errorMessage], 500);
        }
    }

    private function syncArabicProduct(Client $client, $handle, $productId)
    {
        $arabicUrl = 'https://ar.nohanabil.com/products/' . $handle . '.json'; // Assuming Arabic Shopify URL structure

        try {
            // Fetch Arabic product data
            $arabicResponse = $client->get($arabicUrl);
            $arabicData = json_decode($arabicResponse->getBody(), true);

            // Update product with Arabic JSON in `json_ar`
            Product::where('shopify_product_id', $productId)->update([
                'json_ar' => json_encode($arabicData),  // Arabic JSON
            ]);
        } catch (ConnectException $e) {
            // Handle connection timeout or other network-related errors
            Log::warning("Connection error for Arabic product handle: {$handle}. Error: " . $e->getMessage());
        } catch (RequestException $e) {
            // Handle other request exceptions
            Log::warning("Request error for Arabic product handle: {$handle}. Error: " . $e->getMessage());
        }
    }

    private function checkForNextPage($links)
    {
        foreach ($links as $link) {
            if (strpos($link, 'rel="next"') !== false) {
                return true;
            }
        }
        return false;
    }

    private function getNextPageInfo($links)
    {
        foreach ($links as $link) {
            if (preg_match('/<([^>]+)>; rel="next"/', $link, $matches)) {
                $url = $matches[1];
                parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
                return $queryParams['page_info'] ?? null;
            }
        }
        return null;
    }
}
