<?php

namespace App\Http\Controllers;

use App\Models\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;


class ProductSyncController extends Controller
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
    
                    // Dynamically translate the handle to Arabic    
                    // Fetch and store Arabic product JSON
                    $this->syncAlbnainProduct($client, $shopifyProduct['id'], $product->shopify_product_id);
                }
    
                // Check if there is another page
                $links = $response->getHeader('Link');
                $hasNextPage = $this->checkForNextPage($links);
                $pageInfo = $this->getNextPageInfo($links);
            }
    
            return response()->json(['success' => true, 'message' => 'Products synced successfully!'], 200);
        } catch (ConnectException $e) {
            Log::error("Connection error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch products due to connection error. Please try again later.'], 504);
        } catch (RequestException $e) {
            $errorMessage = $e->getResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            Log::error("Request error: " . $errorMessage);
            return response()->json(['error' => 'Failed to fetch products. Error: ' . $errorMessage], 500);
        }
    }


    private function syncAlbnainProduct(Client $client, $handle, $productId)
    {
        // Shopify GraphQL endpoint
        $graphQlUrl = 'https://bonbona-al.myshopify.com/admin/api/2023-04/graphql.json';
    
        $query = <<<GQL
        {
          translatableResource(resourceId: "gid://shopify/Product/{$productId}") {
            resourceId
            translations(locale: "sq") {
              key
              value
            }
          }
        }
        GQL;
    
        try {
            // Execute GraphQL request to fetch the translated handle
            $response = $client->post($graphQlUrl, [
                'headers' => [
                    'X-Shopify-Access-Token' => env('SHOPIFY_ACCESS_TOKEN'),
                    'Content-Type' => 'application/json',  // Set the content type to JSON
                ],
                'json' => ['query' => $query]
            ]);
            $data = json_decode($response->getBody(), true);

            $translations = $data['data']['translatableResource']['translations'];
    
            // Search for the "handle" translation in Albanian
            $albanianHandle = '';
            foreach ($translations as $translation) {
                if ($translation['key'] === 'handle') {
                    $albanianHandle = $translation['value'];
                    break;
                }
            }
    
            // Check if translation for handle exists
            if (empty($albanianHandle)) {
                Log::warning("Albanian handle not found for product ID: {$productId}");
                return;
            }
    
            // Construct the Albanian URL using the translated handle
            $albanianUrl = 'https://bonbona.al/products/' . $albanianHandle . '.json';
    
            // Fetch product details in Albanian
            $albnainResponse = $client->get($albanianUrl);
            $albnainData = json_decode($albnainResponse->getBody(), true);
    
            // Update the product with Albanian JSON in `json_sq`
            Product::where('shopify_product_id', $productId)->update([
                'json_sq' => json_encode($albnainData),  // Albanian JSON
            ]);
        } catch (ConnectException $e) {
            Log::warning("Connection error for Albanian product handle: {$handle}. Error: " . $e->getMessage());
        } catch (RequestException $e) {
            Log::warning("Request error for Albanian product handle: {$handle}. Error: " . $e->getMessage());
        }
    }
    
    
    public function getproduct()
    {
        // Fetch products with 'active' status and non-null 'json_en'
        $products = Product::where('status', 'active')
            ->whereNotNull('json_en') // Ensure json_en is not null
            ->select('json_en') // Select only the JSON fields
            ->get();
    
        // Decode the json_en field for each product (if needed)
        $decodedProducts = $products->map(function ($product) {
            return json_decode($product->json_en, true); // Convert JSON to associative array
        });
    
        // Return the products as a JSON response
        return response()->json([
            'success' => true,
            'products' => $decodedProducts
        ], 200);
    }
    
       

}    