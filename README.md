# SYRO — Modern Shopping. Premium Experience.

A full fashion & lifestyle e-commerce platform for Bangladesh (BDT ৳, Asia/Dhaka), built with **Laravel 12, MySQL, Blade, Bootstrap 5 and Vite**.

## Features

**Storefront** — editorial homepage (hero slider, featured categories, new arrivals, best sellers, trending, promo banners), shop with AJAX filtering (category, brand, price, size, colour, availability, sale) and sorting, live search suggestions, product pages with gallery/zoom and size/colour variant picker, reviews (verified buyers only, moderated), wishlist, cart with coupons, guest or account checkout, order tracking by order number + phone, customer account (orders, cancel, profile, password), newsletter, contact form, content pages.

**Payments** — Cash on Delivery and bKash (Send Money + Transaction ID, verified by an admin). Delivery charges for Inside/Outside Dhaka with a free-delivery threshold, all editable in settings.

**Admin panel** (`/admin`) — sales dashboard with 30-day revenue chart, products (images, variants, stock, SEO), categories, brands, orders (status workflow with automatic stock restore, payment status, printable invoice), customers (activate/deactivate), coupons, review moderation, banners, newsletter export (CSV), contact messages, store settings.

## Local setup (Windows + XAMPP)

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
# create a MySQL database named "syro" (utf8mb4), then:
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Or just double-click **`start-syro.bat`** after the first setup — it starts XAMPP's MySQL and the server, and opens http://127.0.0.1:8000.

### Admin account

Seeding creates the admin **admin@syro.test** with a random password that is printed in the terminal
(or set `SEED_ADMIN_PASSWORD` in `.env` before seeding). Sign in at `/login`, then change the password
under *My profile*. Passwords are never stored in this repository.

Demo coupons: `WELCOME10` (10% off over ৳1,000, max ৳500) and `SYRO500` (৳500 off over ৳5,000).

Demo product images are generated minimalist silhouettes (no copyrighted photos); replace them with real photography from the admin panel.

## Tests

```bash
php artisan test
```

Tests run on an in-memory SQLite database and never touch your MySQL data.
