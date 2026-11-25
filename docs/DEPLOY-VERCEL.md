# Put SYRO online on Vercel (free)

The site runs on **Vercel**, the database on **TiDB Cloud** and product photos on **Cloudinary** —
all three have free plans and none needs a credit card. Every push to GitHub updates the live site automatically.

## 1. TiDB Cloud — the database (5 minutes)

1. Go to **https://tidbcloud.com** → **Sign up** (you can use your GitHub or Google account).
2. Create a cluster: choose **Starter** (free), region **Singapore (ap-southeast-1)** — closest to Bangladesh.
3. When it is ready, click **Connect**:
   - Connection type: **Public** · Connect with: **General**
   - Click **Generate Password** and **copy it now** (it is shown only once).
   - Note the **Host**, **Port** (4000) and **User** (looks like `abc123xyz.root`).
4. Create the database: open **SQL Editor** (left menu) and run:
   ```sql
   CREATE DATABASE syro;
   ```

## 2. Cloudinary — product photos (3 minutes)

1. Go to **https://cloudinary.com** → **Sign up for free**.
2. On the dashboard (**Programmable Media → Dashboard**) find **API environment variable**.
   It looks like `cloudinary://123456789:AbCdEf...@your-cloud-name`. Click to reveal and copy it.

## 3. Fill in the settings file

Open **`D:\syro-\.env.vercel`** in VS Code and replace the four lines marked `<<< FILL IN`:

```
DB_HOST=gateway01.ap-southeast-1.prod.aws.tidbcloud.com
DB_USERNAME=abc123xyz.root
DB_PASSWORD=the-password-you-copied
CLOUDINARY_URL=cloudinary://123456789:AbCdEf...@your-cloud-name
```

Delete the `<<< FILL IN ...` text at the end of each line. Save.
Then tell Claude **"done"** — Claude fills the online database and uploads the photos to Cloudinary for you
(or run it yourself: `php artisan migrate:fresh --seed --force --env=vercel`).

## 4. Push the code to GitHub

In **GitHub Desktop** click **Push origin**.

## 5. Vercel — the website (5 minutes)

1. Go to **https://vercel.com/signup** → **Continue with GitHub** (use your `yeasin-cmt` account).
2. Click **Add New… → Project** → find **syro-** → **Import**.
3. Leave *Framework Preset* as **Other**; do not change build settings (they come from `vercel.json`).
4. Open **Environment Variables**. Open `D:\syro-\.env.vercel`, select all (Ctrl+A), copy, and
   **paste into the first "Key" box** — Vercel splits it into all variables automatically.
5. Click **Deploy** and wait 1–3 minutes.
6. Vercel shows your address, e.g. **`https://syro-abc.vercel.app`**. Open it.

Admin login: **admin@syro.com** / **Syro@2026** → change the password right away (top right → *My profile*).

Optional: in Vercel → Project → **Settings → Environment Variables**, set `APP_URL` to your real address
and click **Redeploy**.

## Updating the site later

1. Make changes on your computer.
2. Run `npm run build` (only needed if you changed CSS/JS in `resources/`).
3. Commit and **Push origin** in GitHub Desktop — Vercel redeploys automatically.

## If something goes wrong

| Problem | Fix |
|---|---|
| **500 error** | Vercel → your project → **Logs**. Most often a wrong `DB_HOST`/`DB_USERNAME`/`DB_PASSWORD`. Fix it in *Settings → Environment Variables*, then **Redeploy**. |
| **Page has no styling** | `public/build` was not pushed. Run `npm run build`, commit, push. |
| **Images missing** | Check `CLOUDINARY_URL` and `PUBLIC_DISK_DRIVER=cloudinary` in Vercel's environment variables. |
| **Slow first load** | Normal: a free serverless site "sleeps" when unused and takes a few seconds to wake up. |
| **Order emails** | Not sent on the free setup (orders still appear in Admin → Orders). |
