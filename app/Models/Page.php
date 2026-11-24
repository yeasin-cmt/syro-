<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Admin-editable content page (About, FAQ, policies…).
 *
 * Body format (plain text, no HTML needed):
 *   ## Heading           → starts a new section (on the FAQ page each heading is a question)
 *   - item               → bullet point
 *   blank line           → new paragraph
 */
class Page extends Model
{
    protected $fillable = ['title', 'slug', 'intro', 'body', 'is_active', 'layout'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<int, array{heading: ?string, blocks: array<int, array{type: string, content: mixed}>}>
     */
    public function sections(): array
    {
        $sections = [];
        $current = ['heading' => null, 'blocks' => []];
        $paragraph = [];
        $list = [];

        $flushParagraph = function () use (&$paragraph, &$current) {
            if ($paragraph) {
                $current['blocks'][] = ['type' => 'p', 'content' => implode(' ', $paragraph)];
                $paragraph = [];
            }
        };
        $flushList = function () use (&$list, &$current) {
            if ($list) {
                $current['blocks'][] = ['type' => 'ul', 'content' => $list];
                $list = [];
            }
        };

        foreach (preg_split('/\R/', (string) $this->body) as $line) {
            $line = trim($line);

            if (Str::startsWith($line, '## ')) {
                $flushParagraph();
                $flushList();
                if ($current['heading'] !== null || $current['blocks']) {
                    $sections[] = $current;
                }
                $current = ['heading' => trim(substr($line, 3)), 'blocks' => []];
            } elseif (Str::startsWith($line, ['- ', '• '])) {
                $flushParagraph();
                $list[] = trim(mb_substr($line, 2));
            } elseif ($line === '') {
                $flushParagraph();
                $flushList();
            } else {
                $flushList();
                $paragraph[] = $line;
            }
        }
        $flushParagraph();
        $flushList();
        if ($current['heading'] !== null || $current['blocks']) {
            $sections[] = $current;
        }

        return $sections;
    }

    /** Starting content, inserted by the migration so the store has its pages from day one. */
    public const DEFAULTS = [
        ['about', 'About SYRO', 'standard', 'SYRO is a Bangladeshi fashion and lifestyle house built on a simple idea: considered design, honest materials and a shopping experience that respects your time.',
            "## Our story\nFounded in Dhaka, SYRO brings together modern silhouettes and everyday wearability. Every piece is chosen for how it looks, how it feels and how long it lasts.\n\nWe work with trusted makers in Bangladesh and abroad, and we test every collection for fit, fabric and finish before it reaches you.\n\n## What we stand for\n- Quality over quantity — fewer, better pieces you will reach for again and again.\n- Transparent pricing in Taka, with no surprises at checkout.\n- Service that feels personal, from your first visit to your next order."],
        ['shipping-returns', 'Shipping & Returns', 'standard', 'We deliver across all 64 districts of Bangladesh.',
            "## Delivery times\n- Inside Dhaka: 1–3 business days.\n- Outside Dhaka: 3–5 business days.\n\nDelivery charges are shown at checkout. Orders above the free-delivery threshold ship free.\n\n## Payment\nPay with Cash on Delivery, or send payment via bKash and enter your Transaction ID at checkout. bKash payments are verified by our team before dispatch.\n\n## Returns & exchanges\nUnworn items with original tags can be returned or exchanged within 7 days of delivery.\n\nContact us with your order number to start a return. Refunds are issued to bKash or bank account within 5–7 business days after we receive the item.\n\nFor hygiene reasons, innerwear and fragrances cannot be returned once opened."],
        ['faq', 'Frequently Asked Questions', 'faq', 'Quick answers to the questions we hear most.',
            "## How do I track my order?\nUse the Track Order page with your order number and phone number, or sign in and open My Orders.\n\n## Can I change or cancel my order?\nYou can cancel from your account while the order is Pending or Confirmed. After it ships, please contact us.\n\n## How do I find my size?\nEach product page has a size guide. If you are between sizes, we recommend sizing up for a relaxed fit.\n\n## Is Cash on Delivery available everywhere?\nYes, Cash on Delivery is available across Bangladesh.\n\n## Do I need an account to order?\nNo. You can check out as a guest, but an account lets you track orders, save a wishlist and review products."],
        ['privacy', 'Privacy Policy', 'standard', 'Your privacy matters to us. This policy explains what we collect and why.',
            "## Information we collect\nYour name, contact details and delivery address when you place an order or create an account, and basic usage data to keep the site secure and working well.\n\n## How we use it\nTo process and deliver orders, provide support, and — only if you subscribe — send our newsletter. We never sell your personal data.\n\n## Your choices\nYou can update your details from your account at any time, unsubscribe from emails, or contact us to delete your account."],
        ['terms', 'Terms & Conditions', 'standard', 'By using this website you agree to the following terms.',
            "## Orders\nAll orders are subject to availability and confirmation. We may cancel an order if an item is out of stock or a price was shown in error, and will notify you if so.\n\n## Pricing\nAll prices are in Bangladeshi Taka (BDT) and include applicable VAT unless stated otherwise.\n\n## Intellectual property\nAll content on this site, including the SYRO name and logo, belongs to SYRO and may not be reused without permission."],
    ];
}
