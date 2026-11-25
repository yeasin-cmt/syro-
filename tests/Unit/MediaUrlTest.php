<?php

namespace Tests\Unit;

use Tests\TestCase;

class MediaUrlTest extends TestCase
{
    public function test_local_disk_uses_relative_storage_url(): void
    {
        $this->assertSame('/storage/products/a.jpg', media_url('products/a.jpg'));
        $this->assertNull(media_url(null));
        $this->assertSame('https://cdn.example.com/x.jpg', media_url('https://cdn.example.com/x.jpg'));
    }

    public function test_cloudinary_url_is_built_without_api_calls(): void
    {
        config(['filesystems.disks.public' => ['driver' => 'cloudinary', 'url' => 'cloudinary://key:secret@syro-shop']]);

        $this->assertSame(
            'https://res.cloudinary.com/syro-shop/image/upload/f_auto,q_auto/demo/products/linen-dress-0',
            media_url('demo/products/linen-dress-0.jpg')
        );
        $this->assertSame(
            'https://res.cloudinary.com/syro-shop/image/upload/f_auto,q_auto/products/AbC123',
            media_url('products/AbC123.webp')
        );
    }

    public function test_vercel_blob_url_is_built_from_the_store_id_in_the_token(): void
    {
        config(['filesystems.disks.public' => ['driver' => 'vercel_blob', 'token' => 'vercel_blob_rw_AbCd1234XyZ_secretpart', 'url' => null]]);

        $this->assertSame(
            'https://abcd1234xyz.public.blob.vercel-storage.com/products/My%20Photo.jpg',
            media_url('products/My Photo.jpg')
        );
    }
}
