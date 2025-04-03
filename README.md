# Laravel MCP Package

A Laravel package for implementing MCP (Model Context Protocol) servers, compatible with Laravel 12 and PHP 8.4.

## Installation

You can install the package via Composer:

```bash
composer require andreagroferreira/laravel-mcp-package
```

## Publishing Configuration

You can publish the configuration file using:

```bash
php artisan vendor:publish --tag=mcp-config
```

This will create a `config/mcp.php` file in your application where you can modify the MCP server settings.

## Basic Usage

After installation, you can use the MCP facade to interact with the MCP service:

```php
use WizardingCode\MCPServer\Facades\MCP;

// Register a static resource
MCP::registerResource('config', 'config://app', function ($uri, $request) {
    return [
        'contents' => [
            [
                'uri' => $uri,
                'text' => 'Application configuration content here',
            ],
        ],
    ];
}, ['description' => 'Application configuration']);
```

## Real-World End-to-End Implementations

### 1. Enterprise Documentation System with Claude AI

This example shows a complete implementation of an MCP server that gives Claude AI access to your company's internal documentation, allowing employees to query company docs right from Claude.

#### Step 1: Create a new Laravel project (if needed)

```bash
composer create-project laravel/laravel documentation-mcp
cd documentation-mcp
```

#### Step 2: Install the MCP package

```bash
composer require andreagroferreira/laravel-mcp-package
```

#### Step 3: Publish the configuration

```bash
php artisan vendor:publish --tag=mcp-config
```

#### Step 4: Create a database migration for storing documentation

```bash
php artisan make:migration create_documents_table
```

Edit the migration file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
            
            $table->index(['category', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
```

Run the migration:

```bash
php artisan migrate
```

#### Step 5: Create a Document model

```bash
php artisan make:model Document
```

Edit the model file:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;
    
    protected $fillable = ['category', 'slug', 'title', 'content'];
}
```

#### Step 6: Create a DocumentationService

Create a new file `app/Services/DocumentationService.php`:

```php
<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;

class DocumentationService
{
    public function getDocument(string $category, string $slug): ?Document
    {
        return Document::where('category', $category)
            ->where('slug', $slug)
            ->first();
    }
    
    public function getAllDocuments(): Collection
    {
        return Document::all();
    }
    
    public function searchDocuments(string $query): Collection
    {
        return Document::where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->get();
    }
}
```

#### Step 7: Set up MCP resources and tools in a service provider

Create a new file `app/Providers/MCPServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Services\DocumentationService;
use Illuminate\Support\ServiceProvider;
use WizardingCode\MCPServer\Facades\MCP;

class MCPServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->setupDocumentationResources();
        $this->setupDocumentationTools();
    }
    
    protected function setupDocumentationResources()
    {
        // Register a document resource template
        MCP::registerResourceTemplate(
            'document',
            'docs://{category}/{slug}',
            function ($uri, $variables, $request) {
                $documentationService = app(DocumentationService::class);
                $document = $documentationService->getDocument(
                    $variables['category'],
                    $variables['slug']
                );
                
                if (!$document) {
                    throw new \Exception("Document not found");
                }
                
                return [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'text' => "# {$document->title}\n\n{$document->content}",
                            'mimeType' => 'text/markdown',
                        ],
                    ],
                ];
            },
            ['description' => 'Company documentation'],
            // List callback for all documents
            function ($request) {
                $documentationService = app(DocumentationService::class);
                $documents = $documentationService->getAllDocuments();
                $resources = [];
                
                foreach ($documents as $doc) {
                    $resources[] = [
                        'uri' => "docs://{$doc->category}/{$doc->slug}",
                        'name' => $doc->title,
                    ];
                }
                
                return ['resources' => $resources];
            }
        );
        
        // Register a document index resource
        MCP::registerResource(
            'document-index',
            'docs://index',
            function ($uri, $request) {
                $documentationService = app(DocumentationService::class);
                $documents = $documentationService->getAllDocuments();
                
                $index = "# Company Documentation Index\n\n";
                $currentCategory = '';
                
                foreach ($documents as $doc) {
                    if ($doc->category !== $currentCategory) {
                        $currentCategory = $doc->category;
                        $index .= "\n## {$currentCategory}\n\n";
                    }
                    
                    $index .= "- [{$doc->title}](docs://{$doc->category}/{$doc->slug})\n";
                }
                
                return [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'text' => $index,
                            'mimeType' => 'text/markdown',
                        ],
                    ],
                ];
            },
            ['description' => 'Documentation index']
        );
    }
    
    protected function setupDocumentationTools()
    {
        // Register a document search tool
        MCP::registerTool(
            'search-docs',
            function ($arguments, $request) {
                $query = $arguments['query'] ?? '';
                
                if (empty($query)) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Search query cannot be empty',
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                $documentationService = app(DocumentationService::class);
                $results = $documentationService->searchDocuments($query);
                
                if ($results->isEmpty()) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "No documents found matching '{$query}'",
                            ],
                        ],
                    ];
                }
                
                $response = "## Search Results for '{$query}'\n\n";
                
                foreach ($results as $doc) {
                    $response .= "- [{$doc->title}](docs://{$doc->category}/{$doc->slug})\n";
                }
                
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $response,
                        ],
                    ],
                ];
            },
            [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string'],
                ],
                'required' => ['query'],
            ],
            'Search company documentation'
        );
    }
}
```

#### Step 8: Register the service provider in `config/app.php`

Add the following to the `providers` array:

```php
App\Providers\MCPServiceProvider::class,
```

#### Step 9: Create a seeder to populate the documentation database

```bash
php artisan make:seeder DocumentSeeder
```

Edit the seeder file:

```php
<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            [
                'category' => 'hr',
                'slug' => 'employee-handbook',
                'title' => 'Employee Handbook',
                'content' => "## Welcome to Our Company\n\nThis handbook contains all the policies and procedures for employees. Please read it carefully.\n\n## Work Hours\n\nStandard work hours are 9 AM to 5 PM, Monday through Friday.\n\n## Benefits\n\nWe offer comprehensive health insurance, 401(k) with matching, and unlimited PTO.",
            ],
            [
                'category' => 'hr',
                'slug' => 'code-of-conduct',
                'title' => 'Code of Conduct',
                'content' => "## Professional Behavior\n\nAll employees are expected to conduct themselves professionally at all times.\n\n## Inclusivity\n\nWe are committed to fostering an inclusive environment for all employees.",
            ],
            [
                'category' => 'engineering',
                'slug' => 'coding-standards',
                'title' => 'Coding Standards',
                'content' => "## General Guidelines\n\n- Use consistent indentation (4 spaces)\n- Write meaningful comments\n- Follow naming conventions\n\n## Code Reviews\n\nAll code must be reviewed by at least one other developer before being merged.",
            ],
            [
                'category' => 'engineering',
                'slug' => 'deployment-process',
                'title' => 'Deployment Process',
                'content' => "## Staging Environment\n\nAll changes must be deployed to staging first.\n\n## Production Deployment\n\nProduction deployments occur every Wednesday at 2 PM.\n\n## Rollback Procedure\n\nIn case of issues, use the rollback script in the deployment repository.",
            ],
        ];
        
        foreach ($documents as $document) {
            Document::create($document);
        }
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=DocumentSeeder
```

#### Step 10: Configure Claude.ai to use your MCP server

1. Start your Laravel server:

```bash
php artisan serve
```

2. In a production environment, make sure your server is accessible via the internet with a valid SSL certificate.

3. In Claude.ai, go to Claude Labs, enable "Use Claude with other apps", and add your MCP server URL:
   - MCP Server URL: `https://your-server.com/api/mcp`

4. Now you can ask Claude questions about your company documentation, such as:
   - "What are our company's work hours?"
   - "What is our deployment process?"
   - "Search for information about code reviews"

---

### 2. E-commerce Integration with Postgres and WhatsApp

This example demonstrates setting up an MCP server that connects to a PostgreSQL database for e-commerce order information and can send WhatsApp notifications to customers.

#### Step 1: Create a new Laravel project (if needed)

```bash
composer create-project laravel/laravel ecommerce-assistant
cd ecommerce-assistant
```

#### Step 2: Install the MCP package and other dependencies

```bash
composer require andreagroferreira/laravel-mcp-package guzzlehttp/guzzle
```

#### Step 3: Set up the database

Configure your `.env` file with PostgreSQL connection details:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecommerce
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

#### Step 4: Create migrations

```bash
php artisan make:migration create_customers_table
php artisan make:migration create_products_table
php artisan make:migration create_orders_table
php artisan make:migration create_order_items_table
```

Edit the migrations:

**create_customers_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
```

**create_products_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

**create_orders_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained();
            $table->string('status')->default('pending');
            $table->decimal('total', 10, 2);
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

**create_order_items_table.php**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
```

Run the migrations:

```bash
php artisan migrate
```

#### Step 5: Create models

```bash
php artisan make:model Customer
php artisan make:model Product
php artisan make:model Order
php artisan make:model OrderItem
```

Edit the models:

**Customer.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'email', 'phone', 'address'];
    
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
```

**Product.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'description', 'price', 'stock'];
    
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

**Order.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    
    protected $fillable = ['customer_id', 'status', 'total', 'shipped_at', 'delivered_at'];
    
    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

**OrderItem.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;
    
    protected $fillable = ['order_id', 'product_id', 'quantity', 'price'];
    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

#### Step 6: Create a seeder to populate the database

```bash
php artisan make:seeder EcommerceSeeder
```

Edit the seeder file:

```php
<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        // Create customers
        $customers = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+12025550177',
                'address' => '123 Main St, New York, NY',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+12025550184',
                'address' => '456 Oak Ave, Los Angeles, CA',
            ],
        ];
        
        foreach ($customers as $customerData) {
            Customer::create($customerData);
        }
        
        // Create products
        $products = [
            [
                'name' => 'Smartphone X',
                'description' => 'Latest smartphone with advanced features',
                'price' => 799.99,
                'stock' => 50,
            ],
            [
                'name' => 'Wireless Headphones',
                'description' => 'Noise-cancelling wireless headphones',
                'price' => 149.99,
                'stock' => 100,
            ],
            [
                'name' => 'Laptop Pro',
                'description' => 'Professional-grade laptop with 16GB RAM',
                'price' => 1299.99,
                'stock' => 25,
            ],
            [
                'name' => 'Smart Watch',
                'description' => 'Fitness tracking and notifications',
                'price' => 199.99,
                'stock' => 75,
            ],
        ];
        
        foreach ($products as $productData) {
            Product::create($productData);
        }
        
        // Create orders
        $orders = [
            [
                'customer_id' => 1,
                'status' => 'shipped',
                'total' => 949.98,
                'shipped_at' => now()->subDays(2),
                'items' => [
                    ['product_id' => 1, 'quantity' => 1, 'price' => 799.99],
                    ['product_id' => 2, 'quantity' => 1, 'price' => 149.99],
                ],
            ],
            [
                'customer_id' => 2,
                'status' => 'pending',
                'total' => 1299.99,
                'items' => [
                    ['product_id' => 3, 'quantity' => 1, 'price' => 1299.99],
                ],
            ],
            [
                'customer_id' => 1,
                'status' => 'delivered',
                'total' => 199.99,
                'shipped_at' => now()->subDays(5),
                'delivered_at' => now()->subDays(3),
                'items' => [
                    ['product_id' => 4, 'quantity' => 1, 'price' => 199.99],
                ],
            ],
        ];
        
        foreach ($orders as $orderData) {
            $items = $orderData['items'];
            unset($orderData['items']);
            
            $order = Order::create($orderData);
            
            foreach ($items as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }
        }
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=EcommerceSeeder
```

#### Step 7: Create services for WhatsApp integration

Create a file `app/Services/WhatsAppService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $apiToken;
    
    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiToken = config('services.whatsapp.api_token');
    }
    
    public function sendMessage(string $recipient, string $message): bool
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->post($this->apiUrl . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipient,
                    'type' => 'text',
                    'text' => [
                        'body' => $message
                    ],
                ]);
            
            if (!$response->successful()) {
                Log::error('WhatsApp API error', [
                    'response' => $response->body(),
                    'recipient' => $recipient,
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('WhatsApp service error', [
                'exception' => $e->getMessage(),
                'recipient' => $recipient,
            ]);
            return false;
        }
    }
}
```

#### Step 8: Update the config file to include WhatsApp settings

Add the following to `config/services.php`:

```php
'whatsapp' => [
    'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0/your-phone-number-id'),
    'api_token' => env('WHATSAPP_API_TOKEN'),
],
```

Update your `.env` file with:

```
WHATSAPP_API_URL=https://graph.facebook.com/v18.0/your-phone-number-id
WHATSAPP_API_TOKEN=your_whatsapp_api_token
```

#### Step 9: Create an E-commerce MCP Service Provider

Create a file `app/Providers/EcommerceMCPServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use WizardingCode\MCPServer\Facades\MCP;

class EcommerceMCPServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register database schema resource
        MCP::registerResource(
            'db-schema',
            'db://schema',
            function ($uri, $request) {
                $tables = DB::select("
                    SELECT 
                        table_name, 
                        column_name, 
                        data_type 
                    FROM 
                        information_schema.columns 
                    WHERE 
                        table_schema = 'public'
                        AND table_name IN ('customers', 'products', 'orders', 'order_items')
                    ORDER BY 
                        table_name, 
                        ordinal_position
                ");
                
                $schema = "# E-commerce Database Schema\n\n";
                $currentTable = '';
                
                foreach ($tables as $column) {
                    if ($column->table_name !== $currentTable) {
                        $currentTable = $column->table_name;
                        $schema .= "\n## Table: {$currentTable}\n\n";
                        $schema .= "| Column | Type |\n";
                        $schema .= "|--------|------|\n";
                    }
                    
                    $schema .= "| {$column->column_name} | {$column->data_type} |\n";
                }
                
                return [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'text' => $schema,
                            'mimeType' => 'text/markdown',
                        ],
                    ],
                ];
            },
            ['description' => 'E-commerce database schema']
        );
        
        // Register orders resource
        MCP::registerResource(
            'orders',
            'ecommerce://orders',
            function ($uri, $request) {
                $orders = Order::with(['customer', 'items.product'])->get();
                
                $orderList = "# All Orders\n\n";
                $orderList .= "| Order ID | Customer | Status | Total | Date |\n";
                $orderList .= "|----------|----------|--------|-------|------|\n";
                
                foreach ($orders as $order) {
                    $orderList .= "| #{$order->id} | {$order->customer->name} | {$order->status} | \${$order->total} | {$order->created_at->format('Y-m-d')} |\n";
                }
                
                return [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'text' => $orderList,
                            'mimeType' => 'text/markdown',
                        ],
                    ],
                ];
            },
            ['description' => 'All orders']
        );
        
        // Register order details resource template
        MCP::registerResourceTemplate(
            'order-detail',
            'ecommerce://orders/{id}',
            function ($uri, $variables, $request) {
                $orderId = $variables['id'];
                $order = Order::with(['customer', 'items.product'])->find($orderId);
                
                if (!$order) {
                    throw new \Exception("Order #{$orderId} not found");
                }
                
                $orderDetail = "# Order #{$order->id}\n\n";
                $orderDetail .= "**Customer**: {$order->customer->name}\n";
                $orderDetail .= "**Email**: {$order->customer->email}\n";
                $orderDetail .= "**Phone**: {$order->customer->phone}\n";
                $orderDetail .= "**Status**: {$order->status}\n";
                $orderDetail .= "**Total**: \${$order->total}\n";
                $orderDetail .= "**Date**: {$order->created_at->format('Y-m-d')}\n\n";
                
                if ($order->shipped_at) {
                    $orderDetail .= "**Shipped**: {$order->shipped_at->format('Y-m-d')}\n";
                }
                
                if ($order->delivered_at) {
                    $orderDetail .= "**Delivered**: {$order->delivered_at->format('Y-m-d')}\n";
                }
                
                $orderDetail .= "\n## Items\n\n";
                $orderDetail .= "| Product | Quantity | Price | Subtotal |\n";
                $orderDetail .= "|---------|----------|-------|----------|\n";
                
                foreach ($order->items as $item) {
                    $subtotal = $item->quantity * $item->price;
                    $orderDetail .= "| {$item->product->name} | {$item->quantity} | \${$item->price} | \${$subtotal} |\n";
                }
                
                return [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'text' => $orderDetail,
                            'mimeType' => 'text/markdown',
                        ],
                    ],
                ];
            },
            ['description' => 'Order details'],
            // List callback
            function ($request) {
                $orders = Order::all();
                $resources = [];
                
                foreach ($orders as $order) {
                    $resources[] = [
                        'uri' => "ecommerce://orders/{$order->id}",
                        'name' => "Order #{$order->id}",
                    ];
                }
                
                return ['resources' => $resources];
            }
        );
        
        // Register query tool
        MCP::registerTool(
            'query-orders',
            function ($arguments, $request) {
                $status = $arguments['status'] ?? null;
                $customer = $arguments['customer'] ?? null;
                
                $query = Order::with(['customer', 'items.product']);
                
                if ($status) {
                    $query->where('status', $status);
                }
                
                if ($customer) {
                    $query->whereHas('customer', function ($q) use ($customer) {
                        $q->where('name', 'like', "%{$customer}%")
                          ->orWhere('email', 'like', "%{$customer}%");
                    });
                }
                
                $orders = $query->get();
                
                if ($orders->isEmpty()) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'No orders found matching your criteria.',
                            ],
                        ],
                    ];
                }
                
                $result = "# Order Search Results\n\n";
                $result .= "| Order ID | Customer | Status | Total | Date |\n";
                $result .= "|----------|----------|--------|-------|------|\n";
                
                foreach ($orders as $order) {
                    $result .= "| #{$order->id} | {$order->customer->name} | {$order->status} | \${$order->total} | {$order->created_at->format('Y-m-d')} |\n";
                }
                
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $result,
                        ],
                    ],
                ];
            },
            [
                'type' => 'object',
                'properties' => [
                    'status' => ['type' => 'string'],
                    'customer' => ['type' => 'string'],
                ],
            ],
            'Search for orders by status or customer'
        );
        
        // Register update order status tool
        MCP::registerTool(
            'update-order-status',
            function ($arguments, $request) {
                $orderId = $arguments['order_id'] ?? null;
                $newStatus = $arguments['status'] ?? null;
                
                if (!$orderId || !$newStatus) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Order ID and new status are required.',
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                if (!in_array($newStatus, $allowedStatuses)) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Invalid status. Allowed values: " . implode(', ', $allowedStatuses),
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                $order = Order::with('customer')->find($orderId);
                if (!$order) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Order #{$orderId} not found.",
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                $oldStatus = $order->status;
                $order->status = $newStatus;
                
                // Update timestamps based on status
                if ($newStatus === 'shipped' && !$order->shipped_at) {
                    $order->shipped_at = now();
                }
                
                if ($newStatus === 'delivered' && !$order->delivered_at) {
                    $order->delivered_at = now();
                }
                
                $order->save();
                
                // Send WhatsApp notification if customer has a phone number
                $notificationSent = false;
                if ($order->customer->phone) {
                    $notificationSent = $this->sendStatusUpdateNotification($order);
                }
                
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Order #{$orderId} status updated from '{$oldStatus}' to '{$newStatus}'." . 
                                     ($notificationSent ? " Customer notified via WhatsApp." : ""),
                        ],
                    ],
                ];
            },
            [
                'type' => 'object',
                'properties' => [
                    'order_id' => ['type' => 'number'],
                    'status' => ['type' => 'string'],
                ],
                'required' => ['order_id', 'status'],
            ],
            'Update order status'
        );
        
        // Register send WhatsApp notification tool
        MCP::registerTool(
            'send-whatsapp-notification',
            function ($arguments, $request) {
                $customerId = $arguments['customer_id'] ?? null;
                $message = $arguments['message'] ?? '';
                
                if (!$customerId || empty($message)) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Customer ID and message are required.',
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                $customer = Customer::find($customerId);
                if (!$customer) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Customer #{$customerId} not found.",
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                if (!$customer->phone) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Customer #{$customerId} does not have a phone number.",
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                $whatsAppService = app(WhatsAppService::class);
                $success = $whatsAppService->sendMessage($customer->phone, $message);
                
                if ($success) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "WhatsApp message sent to {$customer->name}.",
                            ],
                        ],
                    ];
                } else {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Failed to send WhatsApp message to {$customer->name}.",
                            ],
                        ],
                        'isError' => true,
                    ];
                }
            },
            [
                'type' => 'object',
                'properties' => [
                    'customer_id' => ['type' => 'number'],
                    'message' => ['type' => 'string'],
                ],
                'required' => ['customer_id', 'message'],
            ],
            'Send WhatsApp notification to a customer'
        );
    }
    
    protected function sendStatusUpdateNotification(Order $order): bool
    {
        $statusMessages = [
            'processing' => "Your order #{$order->id} is now being processed. We'll update you when it ships.",
            'shipped' => "Good news! Your order #{$order->id} has been shipped and is on its way to you.",
            'delivered' => "Your order #{$order->id} has been delivered. Thank you for shopping with us!",
            'cancelled' => "Your order #{$order->id} has been cancelled. Please contact customer service for assistance.",
        ];
        
        if (!isset($statusMessages[$order->status])) {
            return false;
        }
        
        $whatsAppService = app(WhatsAppService::class);
        return $whatsAppService->sendMessage(
            $order->customer->phone,
            $statusMessages[$order->status]
        );
    }
}
```

#### Step 10: Register the EcommerceMCPServiceProvider in `config/app.php`

Add the following to the `providers` array:

```php
App\Providers\EcommerceMCPServiceProvider::class,
```

#### Step 11: Configure and start the server

```bash
php artisan serve
```

Now your MCP server is ready to be used by Claude or other AI assistants. Connect it using:

MCP Server URL: `http://localhost:8000/api/mcp`

You can now ask questions like:
- "Show me all orders in the system"
- "What's the status of order #1?"
- "Find all orders that are shipped"
- "Update order #2 status to shipped"
- "Send a notification to customer #1 about their order status"

---

### 3. Business Intelligence Dashboard Integration with n8n

This example shows how to build an MCP server that can connect to various data sources and trigger n8n workflows for business intelligence tasks.

#### Step 1: Create a new Laravel project

```bash
composer create-project laravel/laravel bi-assistant
cd bi-assistant
```

#### Step 2: Install the MCP package and required dependencies

```bash
composer require andreagroferreira/laravel-mcp-package guzzlehttp/guzzle league/csv
```

#### Step 3: Create sample data services

Create the file `app/Services/SalesDataService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use League\Csv\Reader;
use League\Csv\Writer;

class SalesDataService
{
    protected string $dataPath;
    
    public function __construct()
    {
        $this->dataPath = storage_path('app/data');
        
        if (!file_exists($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
        
        $this->ensureSalesDataExists();
    }
    
    public function getSalesData(): Collection
    {
        $csv = Reader::createFromPath($this->dataPath . '/sales_data.csv', 'r');
        $csv->setHeaderOffset(0);
        
        $records = collect($csv->getRecords());
        
        // Convert string values to appropriate types
        return $records->map(function ($record) {
            return [
                'date' => $record['date'],
                'product' => $record['product'],
                'region' => $record['region'],
                'sales' => (float) $record['sales'],
                'units' => (int) $record['units'],
            ];
        });
    }
    
    public function getSalesByRegion(): array
    {
        $salesData = $this->getSalesData();
        
        $regions = $salesData->groupBy('region')
            ->map(function ($items, $region) {
                return [
                    'region' => $region,
                    'total_sales' => $items->sum('sales'),
                    'total_units' => $items->sum('units'),
                ];
            })
            ->values()
            ->all();
            
        return $regions;
    }
    
    public function getSalesByProduct(): array
    {
        $salesData = $this->getSalesData();
        
        $products = $salesData->groupBy('product')
            ->map(function ($items, $product) {
                return [
                    'product' => $product,
                    'total_sales' => $items->sum('sales'),
                    'total_units' => $items->sum('units'),
                ];
            })
            ->values()
            ->all();
            
        return $products;
    }
    
    public function getSalesByDate(string $startDate = null, string $endDate = null): array
    {
        $salesData = $this->getSalesData();
        
        if ($startDate) {
            $salesData = $salesData->filter(function ($item) use ($startDate) {
                return $item['date'] >= $startDate;
            });
        }
        
        if ($endDate) {
            $salesData = $salesData->filter(function ($item) use ($endDate) {
                return $item['date'] <= $endDate;
            });
        }
        
        return [
            'total_sales' => $salesData->sum('sales'),
            'total_units' => $salesData->sum('units'),
            'average_daily_sales' => $salesData->groupBy('date')->count() > 0 
                ? $salesData->sum('sales') / $salesData->groupBy('date')->count() 
                : 0,
        ];
    }
    
    protected function ensureSalesDataExists(): void
    {
        $filePath = $this->dataPath . '/sales_data.csv';
        
        if (file_exists($filePath)) {
            return;
        }
        
        // Create sample sales data
        $sampleData = [
            ['date', 'product', 'region', 'sales', 'units'],
            ['2023-01-01', 'Product A', 'North', '5000', '100'],
            ['2023-01-01', 'Product B', 'North', '3000', '50'],
            ['2023-01-01', 'Product A', 'South', '4500', '90'],
            ['2023-01-02', 'Product B', 'South', '3500', '60'],
            ['2023-01-02', 'Product C', 'East', '2000', '40'],
            ['2023-01-03', 'Product A', 'West', '6000', '120'],
            ['2023-01-03', 'Product C', 'North', '2500', '50'],
            ['2023-01-04', 'Product B', 'East', '3200', '55'],
            ['2023-01-04', 'Product A', 'South', '4800', '95'],
            ['2023-01-05', 'Product C', 'West', '2200', '45'],
            ['2023-01-05', 'Product B', 'North', '3100', '52'],
            ['2023-01-06', 'Product A', 'East', '5500', '110'],
            ['2023-01-06', 'Product C', 'South', '2300', '46'],
            ['2023-01-07', 'Product B', 'West', '3400', '58'],
            ['2023-01-07', 'Product A', 'North', '5200', '105'],
        ];
        
        $csv = Writer::createFromPath($filePath, 'w+');
        $csv->insertAll($sampleData);
    }
}
```

Create the file `app/Services/N8NService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8NService
{
    protected string $webhookBaseUrl;
    
    public function __construct()
    {
        $this->webhookBaseUrl = config('services.n8n.webhook_base_url');
    }
    
    public function executeWorkflow(string $workflowId, array $data = []): array
    {
        $url = $this->webhookBaseUrl . '/' . $workflowId;
        
        try {
            $response = Http::post($url, $data);
            
            if ($response->failed()) {
                Log::error('N8N workflow execution failed', [
                    'workflow' => $workflowId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                
                throw new \Exception('Workflow execution failed: ' . $response->body());
            }
            
            return $response->json() ?: ['success' => true];
        } catch (\Exception $e) {
            Log::error('N8N service error', [
                'workflow' => $workflowId,
                'exception' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function getAvailableWorkflows(): array
    {
        // In a real implementation, you would fetch this from n8n's API
        // Here we're using mock data
        return [
            [
                'id' => 'generate-sales-report',
                'name' => 'Generate Sales Report',
                'description' => 'Creates a PDF sales report and emails it to specified recipients',
            ],
            [
                'id' => 'export-to-sheets',
                'name' => 'Export to Google Sheets',
                'description' => 'Exports data to a Google Sheets document',
            ],
            [
                'id' => 'update-dashboard',
                'name' => 'Update BI Dashboard',
                'description' => 'Refreshes the Power BI dashboard with latest data',
            ],
            [
                'id' => 'alert-sales-threshold',
                'name' => 'Sales Threshold Alert',
                'description' => 'Sends alerts when sales fall below or exceed thresholds',
            ],
        ];
    }
}
```

#### Step 4: Update your config file to include n8n settings

Add to `config/services.php`:

```php
'n8n' => [
    'webhook_base_url' => env('N8N_WEBHOOK_BASE_URL', 'https://n8n.yourcompany.com/webhook'),
],
```

Update your `.env` file:

```
N8N_WEBHOOK_BASE_URL=https://n8n.yourcompany.com/webhook
```

#### Step 5: Create the MCP service provider

Create the file `app/Providers/BIMCPServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Services\N8NService;
use App\Services\SalesDataService;
use Illuminate\Support\ServiceProvider;
use WizardingCode\MCPServer\Facades\MCP;

class BIMCPServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->setupSalesDataResources();
        $this->setupN8NTools();
    }
    
    protected function setupSalesDataResources()
    {
        // Sales overview resource
        MCP::registerResource(
            'sales-overview',
            'bi://sales/overview',
            function ($uri, $request) {
                $salesDataService = app(SalesDataService::class);
                $salesByRegion = $salesDataService->getSalesByRegion();
                $salesByProduct = $salesDataService->getSalesByProduct();
                $salesOverall = $salesDataService->getSalesByDate();
                
                $overview = "# Sales Data Overview\n\n";
                $overview .= "## Overall Metrics\n\n";
                $overview .= "- **Total Sales**: \${$salesOverall['total_sales']}\n";
                $overview .= "- **Total Units**: {$salesOverall['total_units']}\n";
                $overview .= "- **Average Daily Sales**: \${$salesOverall['average_daily_sales']}\n\n";
                
                $overview .= "## Sales by Region\n\n";
                $overview .= "| Region | Total Sales | Total Units |\n";
                $overview .= "|--------|-------------|-------------|\n";
                
                foreach ($salesByRegion as $region) {
                    $overview .= "| {$region['region']} | \${$region['total_sales']} | {$region['total_units']} |\n";
                }
                
                $overview .= "\n## Sales by Product\n\n";
                $overview .= "| Product | Total Sales | Total Units |\n";
                $overview .= "|---------|-------------|-------------|\n";
                
                foreach ($salesByProduct as $product) {
                    $overview .= "| {$product['product']} | \${$product['total_sales']} | {$product['total_units']} |\n";
                }
                
                return [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'text' => $overview,
                            'mimeType' => 'text/markdown',
                        ],
                    ],
                ];
            },
            ['description' => 'Sales data overview']
        );
        
        // Sales data by region resource template
        MCP::registerResourceTemplate(
            'sales-by-region',
            'bi://sales/regions/{region}',
            function ($uri, $variables, $request) {
                $region = $variables['region'];
                $salesDataService = app(SalesDataService::class);
                $allSalesData = $salesDataService->getSalesData();
                
                $regionSales = $allSalesData->filter(function ($item) use ($region) {
                    return strtolower($item['region']) === strtolower($region);
                });
                
                if ($regionSales->isEmpty()) {
                    throw new \Exception("No sales data found for region: {$region}");
                }
                
                $totalSales = $regionSales->sum('sales');
                $totalUnits = $regionSales->sum('units');
                
                $salesByProduct = $regionSales->groupBy('product')
                    ->map(function ($items, $product) {
                        return [
                            'product' => $product,
                            'total_sales' => $items->sum('sales'),
                            'total_units' => $items->sum('units'),
                        ];
                    })
                    ->values()
                    ->all();
                
                $salesByDate = $regionSales->groupBy('date')
                    ->map(function ($items, $date) {
                        return [
                            'date' => $date,
                            'total_sales' => $items->sum('sales'),
                            'total_units' => $items->sum('units'),
                        ];
                    })
                    ->values()
                    ->all();
                
                $report = "# Sales Report for {$region} Region\n\n";
                $report .= "## Summary\n\n";
                $report .= "- **Total Sales**: \${$totalSales}\n";
                $report .= "- **Total Units**: {$totalUnits}\n\n";
                
                $report .= "## Sales by Product\n\n";
                $report .= "| Product | Total Sales | Total Units |\n";
                $report .= "|---------|-------------|-------------|\n";
                
                foreach ($salesByProduct as $product) {
                    $report .= "| {$product['product']} | \${$product['total_sales']} | {$product['total_units']} |\n";
                }
                
                $report .= "\n## Daily Sales\n\n";
                $report .= "| Date | Total Sales | Total Units |\n";
                $report .= "|------|-------------|-------------|\n";
                
                foreach ($salesByDate as $day) {
                    $report .= "| {$day['date']} | \${$day['total_sales']} | {$day['total_units']} |\n";
                }
                
                return [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'text' => $report,
                            'mimeType' => 'text/markdown',
                        ],
                    ],
                ];
            },
            ['description' => 'Sales data by region'],
            // List callback
            function ($request) {
                $salesDataService = app(SalesDataService::class);
                $regions = collect($salesDataService->getSalesByRegion());
                
                return [
                    'resources' => $regions->map(function ($region) {
                        return [
                            'uri' => "bi://sales/regions/{$region['region']}",
                            'name' => "{$region['region']} Region Sales",
                        ];
                    })->all(),
                ];
            }
        );
        
        // Sales data CSV resource
        MCP::registerResource(
            'sales-data-csv',
            'bi://sales/data.csv',
            function ($uri, $request) {
                $salesDataService = app(SalesDataService::class);
                $salesData = $salesDataService->getSalesData();
                
                $csvContent = "date,product,region,sales,units\n";
                
                foreach ($salesData as $row) {
                    $csvContent .= "{$row['date']},{$row['product']},{$row['region']},{$row['sales']},{$row['units']}\n";
                }
                
                return [
                    'contents' => [
                        [
                            'uri' => $uri,
                            'text' => $csvContent,
                            'mimeType' => 'text/csv',
                        ],
                    ],
                ];
            },
            ['description' => 'Raw sales data in CSV format']
        );
    }
    
    protected function setupN8NTools()
    {
        // List workflows tool
        MCP::registerTool(
            'list-workflows',
            function ($arguments, $request) {
                $n8nService = app(N8NService::class);
                $workflows = $n8nService->getAvailableWorkflows();
                
                $response = "# Available n8n Workflows\n\n";
                
                foreach ($workflows as $workflow) {
                    $response .= "## {$workflow['name']}\n\n";
                    $response .= "**ID**: `{$workflow['id']}`\n\n";
                    $response .= "**Description**: {$workflow['description']}\n\n";
                    $response .= "---\n\n";
                }
                
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $response,
                        ],
                    ],
                ];
            },
            [],
            'List all available n8n workflows'
        );
        
        // Execute workflow tool
        MCP::registerTool(
            'execute-workflow',
            function ($arguments, $request) {
                $workflowId = $arguments['workflow_id'] ?? null;
                $params = $arguments['params'] ?? [];
                
                if (!$workflowId) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Workflow ID is required.',
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                try {
                    $n8nService = app(N8NService::class);
                    $result = $n8nService->executeWorkflow($workflowId, $params);
                    
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Workflow '{$workflowId}' executed successfully: " . json_encode($result, JSON_PRETTY_PRINT),
                            ],
                        ],
                    ];
                } catch (\Exception $e) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Error executing workflow: " . $e->getMessage(),
                            ],
                        ],
                        'isError' => true,
                    ];
                }
            },
            [
                'type' => 'object',
                'properties' => [
                    'workflow_id' => ['type' => 'string'],
                    'params' => ['type' => 'object'],
                ],
                'required' => ['workflow_id'],
            ],
            'Execute an n8n workflow'
        );
        
        // Generate sales report tool
        MCP::registerTool(
            'generate-sales-report',
            function ($arguments, $request) {
                $region = $arguments['region'] ?? null;
                $startDate = $arguments['start_date'] ?? null;
                $endDate = $arguments['end_date'] ?? null;
                $emailTo = $arguments['email_to'] ?? null;
                
                if (!$emailTo) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Email address is required.',
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                try {
                    $n8nService = app(N8NService::class);
                    $result = $n8nService->executeWorkflow('generate-sales-report', [
                        'region' => $region,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'emailTo' => $emailTo,
                    ]);
                    
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Sales report generation triggered. It will be sent to {$emailTo} when ready.",
                            ],
                        ],
                    ];
                } catch (\Exception $e) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Error generating sales report: " . $e->getMessage(),
                            ],
                        ],
                        'isError' => true,
                    ];
                }
            },
            [
                'type' => 'object',
                'properties' => [
                    'region' => ['type' => 'string'],
                    'start_date' => ['type' => 'string'],
                    'end_date' => ['type' => 'string'],
                    'email_to' => ['type' => 'string'],
                ],
                'required' => ['email_to'],
            ],
            'Generate and email a sales report'
        );
        
        // Export data to Google Sheets
        MCP::registerTool(
            'export-to-sheets',
            function ($arguments, $request) {
                $sheetId = $arguments['sheet_id'] ?? null;
                $region = $arguments['region'] ?? null;
                
                if (!$sheetId) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Google Sheet ID is required.',
                            ],
                        ],
                        'isError' => true,
                    ];
                }
                
                try {
                    $salesDataService = app(SalesDataService::class);
                    $salesData = $salesDataService->getSalesData();
                    
                    if ($region) {
                        $salesData = $salesData->filter(function ($item) use ($region) {
                            return strtolower($item['region']) === strtolower($region);
                        });
                    }
                    
                    if ($salesData->isEmpty()) {
                        return [
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => "No data to export" . ($region ? " for region {$region}" : ""),
                                ],
                            ],
                            'isError' => true,
                        ];
                    }
                    
                    $n8nService = app(N8NService::class);
                    $n8nService->executeWorkflow('export-to-sheets', [
                        'sheetId' => $sheetId,
                        'data' => $salesData->toArray(),
                    ]);
                    
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Sales data" . ($region ? " for region {$region}" : "") . 
                                         " exported to Google Sheet: {$sheetId}",
                            ],
                        ],
                    ];
                } catch (\Exception $e) {
                    return [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Error exporting to Google Sheets: " . $e->getMessage(),
                            ],
                        ],
                        'isError' => true,
                    ];
                }
            },
            [
                'type' => 'object',
                'properties' => [
                    'sheet_id' => ['type' => 'string'],
                    'region' => ['type' => 'string'],
                ],
                'required' => ['sheet_id'],
            ],
            'Export sales data to Google Sheets'
        );
    }
}
```

#### Step 6: Register the BIMCPServiceProvider in `config/app.php`

Add the following to the `providers` array:

```php
App\Providers\BIMCPServiceProvider::class,
```

#### Step 7: Start the server

```bash
php artisan serve
```

Now your business intelligence MCP server is ready for use. Connect it to Claude AI or other AI assistants using:

MCP Server URL: `http://localhost:8000/api/mcp`

You can now ask questions like:
- "Show me an overview of all sales data"
- "What are the sales figures for the North region?"
- "List all available n8n workflows"
- "Generate a sales report for the South region and email it to me@example.com"
- "Export the East region sales data to Google Sheets with ID 1ABC123XYZ"
- "What were our total sales for Product A?"

## Testing

```bash
composer test
```

## License

This package is open-source software licensed under the [MIT license](LICENSE.md).