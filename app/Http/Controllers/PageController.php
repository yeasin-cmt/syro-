<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    /**
     * Static content pages: slug => [title, intro, sections[heading => paragraphs[]]].
     */
    public const PAGES = [
        'about' => [
            'title' => 'About SYRO',
            'intro' => 'SYRO is a Bangladeshi fashion and lifestyle house built on a simple idea: considered design, honest materials and a shopping experience that respects your time.',
            'sections' => [
                'Our story' => [
                    'Founded in Dhaka, SYRO brings together modern silhouettes and everyday wearability. Every piece is chosen for how it looks, how it feels and how long it lasts.',
                    'We work with trusted makers in Bangladesh and abroad, and we test every collection for fit, fabric and finish before it reaches you.',
                ],
                'What we stand for' => [
                    'Quality over quantity — fewer, better pieces you will reach for again and again.',
                    'Transparent pricing in Taka, with no surprises at checkout.',
                    'Service that feels personal, from your first visit to your next order.',
                ],
            ],
        ],
        'shipping-returns' => [
            'title' => 'Shipping & Returns',
            'intro' => 'We deliver across all 64 districts of Bangladesh.',
            'sections' => [
                'Delivery times' => [
                    'Inside Dhaka: 1–3 business days.',
                    'Outside Dhaka: 3–5 business days.',
                    'Delivery charges are shown at checkout. Orders above the free-delivery threshold ship free.',
                ],
                'Payment' => [
                    'Pay with Cash on Delivery, or send payment via bKash and enter your Transaction ID at checkout. bKash payments are verified by our team before dispatch.',
                ],
                'Returns & exchanges' => [
                    'Unworn items with original tags can be returned or exchanged within 7 days of delivery.',
                    'Contact us with your order number to start a return. Refunds are issued to bKash or bank account within 5–7 business days after we receive the item.',
                    'For hygiene reasons, innerwear and fragrances cannot be returned once opened.',
                ],
            ],
        ],
        'faq' => [
            'title' => 'Frequently Asked Questions',
            'intro' => 'Quick answers to the questions we hear most.',
            'sections' => [
                'How do I track my order?' => ['Use the Track Order page with your order number and phone number, or sign in and open My Orders.'],
                'Can I change or cancel my order?' => ['You can cancel from your account while the order is Pending or Confirmed. After it ships, please contact us.'],
                'How do I find my size?' => ['Each product page lists available sizes. If you are between sizes, we recommend sizing up for a relaxed fit.'],
                'Is Cash on Delivery available everywhere?' => ['Yes, Cash on Delivery is available across Bangladesh.'],
                'Do I need an account to order?' => ['No. You can check out as a guest, but an account lets you track orders, save a wishlist and review products.'],
            ],
        ],
        'privacy' => [
            'title' => 'Privacy Policy',
            'intro' => 'Your privacy matters to us. This policy explains what we collect and why.',
            'sections' => [
                'Information we collect' => ['Your name, contact details and delivery address when you place an order or create an account, and basic usage data to keep the site secure and working well.'],
                'How we use it' => ['To process and deliver orders, provide support, and — only if you subscribe — send our newsletter. We never sell your personal data.'],
                'Your choices' => ['You can update your details from your account at any time, unsubscribe from emails, or contact us to delete your account.'],
            ],
        ],
        'terms' => [
            'title' => 'Terms & Conditions',
            'intro' => 'By using this website you agree to the following terms.',
            'sections' => [
                'Orders' => ['All orders are subject to availability and confirmation. We may cancel an order if an item is out of stock or a price was shown in error, and will notify you if so.'],
                'Pricing' => ['All prices are in Bangladeshi Taka (BDT) and include applicable VAT unless stated otherwise.'],
                'Intellectual property' => ['All content on this site, including the SYRO name and logo, belongs to SYRO and may not be reused without permission.'],
            ],
        ],
    ];

    public function show(string $page)
    {
        return view('pages.static', ['page' => self::PAGES[$page], 'slug' => $page]);
    }
}
