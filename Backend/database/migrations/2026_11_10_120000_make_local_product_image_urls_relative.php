<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Imagens da própria loja eram gravadas com o domínio (APP_URL) da época.
     * Passam a guardar só o caminho, que vale em qualquer domínio. URLs externas
     * ficam como estão: só é convertida a URL cujo arquivo existe neste servidor.
     */
    public function up(): void
    {
        $products = DB::table('products')->where('image_url', 'like', 'http%/products/%')->get(['id', 'image_url']);
        foreach ($products as $product) {
            if (! preg_match('#^https?://[^/]+(/(storage|images)/products/([^/?\#]+))$#', $product->image_url, $match)) {
                continue;
            }
            $exists = $match[2] === 'storage'
                ? Storage::disk('public')->exists('products/'.$match[3])
                : is_file(public_path('images/products/'.$match[3]));
            if ($exists) {
                DB::table('products')->where('id', $product->id)->update(['image_url' => $match[1]]);
            }
        }
    }

    public function down(): void
    {
        // Sem volta: o caminho relativo funciona em qualquer domínio.
    }
};
