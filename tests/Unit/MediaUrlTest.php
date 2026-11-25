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
}
