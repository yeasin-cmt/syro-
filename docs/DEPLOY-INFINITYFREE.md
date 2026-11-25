# Put SYRO online for free (InfinityFree)

Your site will stay online 24/7, even when your computer is off.
Total time: about 30–45 minutes the first time.

You need these files from your computer (built by `scripts\build-infinityfree.ps1`):

| File / folder | What it is |
|---|---|
| `D:\syro-\deploy\infinityfree\htdocs\` | The complete website |
| `D:\syro-\deploy\infinityfree\upload-zips\` | The same website, packed in small zips |
| `D:\syro-\deploy\infinityfree\syro-database.sql` | Your products, orders, pages and admin accounts |

---

## Step 1 — Create a free account and website

1. Go to **https://www.infinityfree.com** and click **Register**. Sign up with your email and confirm it.
2. Click **Create Account** (this means "create a website").
3. Choose **Subdomain**, type a name such as `syro-bd`, and pick an ending such as `.infinityfreeapp.com`.
   Your address will be like **`https://syro-bd.infinityfreeapp.com`** — write it down.
4. Finish, then wait until the account shows **Active** (a few minutes).

## Step 2 — Create the database

1. Open your account → **Control Panel** (vPanel).
2. Click **MySQL Databases**. Type `syro` and click **Create Database**.
3. Write down these four things shown on that page / in the client area:
   - **MySQL Host Name** — like `sql123.infinityfree.com`
   - **MySQL Database Name** — like `if0_12345678_syro`
   - **MySQL User Name** — like `if0_12345678`
   - **MySQL Password** — your vPanel password (client area → your account → **Show password**)

## Step 3 — Import your data

1. In the Control Panel, click **phpMyAdmin** next to your new database.
2. Click your database name on the left (`if0_..._syro`).
3. Click the **Import** tab → **Choose file** → select `syro-database.sql` → click **Import** (bottom).
4. You should see a green "Import has been successfully finished" message and tables on the left.

## Step 4 — Enter your database details

Open `D:\syro-\deploy\infinityfree\htdocs\.env` in VS Code (it's a hidden file — use **File → Open File**).
Change only these lines, using what you wrote down in Step 1 and 2:

```
APP_URL=https://syro-bd.infinityfreeapp.com
DB_HOST=sql123.infinityfree.com
DB_DATABASE=if0_12345678_syro
DB_USERNAME=if0_12345678
DB_PASSWORD=your-vpanel-password
```

Save the file.

## Step 5 — Upload the website (FileZilla, most reliable)

1. Install **FileZilla Client** (free) from https://filezilla-project.org.
2. In InfinityFree client area → your account → **FTP Details**: copy **FTP Hostname**, **FTP Username**, **FTP Password**, port **21**.
3. In FileZilla: fill in Host, Username, Password, Port 21 → **Quickconnect**.
4. On the right side (the server), double-click the **htdocs** folder. Delete the file `index2.html` if present.
5. On the left side (your computer), open `D:\syro-\deploy\infinityfree\htdocs\`.
6. Select **everything inside it** (Ctrl+A — include `.env` and `.htaccess`; turn on
   *Server → Force showing hidden files* if you don't see them) and drag it into the right-side **htdocs**.
7. Wait until the queue at the bottom is empty (several thousand small files — around 15–30 minutes).
   If some files show "failed", right-click the failed list → **Reset and requeue all**.

> Prefer zips? If your InfinityFree File Manager offers an **Extract** option, you can instead upload
> the files from `upload-zips\` into `htdocs` one by one and extract each (then delete the zip).
> Remember to edit `.env` in the File Manager afterwards (Step 4 values).

## Step 6 — Turn on HTTPS (free)

Client area → your account → **Free SSL Certificates** → **New SSL Certificate** → choose your domain →
follow the steps (it adds a DNS record automatically for InfinityFree subdomains). It can take a while to become active.
Until then, use `http://` instead of `https://`.

## Step 7 — Open your shop

- Shop: `https://syro-bd.infinityfreeapp.com`
- Admin: `https://syro-bd.infinityfreeapp.com/login` — sign in with your admin email and password
  (the same ones you use on your computer, because the database was copied).

**First thing after signing in:** change your password (top right → *My profile*), and set your real phone,
bKash number and delivery charges in **Admin → Settings**.

---

## If something goes wrong

| What you see | Fix |
|---|---|
| **HTTP 500 / "Server Error"** | Usually wrong database details in `.env` (Step 4). Check host, name, user and password carefully, re-upload `.env`. |
| **"Index of /" or the InfinityFree welcome page** | Files are not directly inside `htdocs`, or `.htaccess` is missing. You should see `app`, `public`, `vendor`, `.htaccess`, `.env` directly in `htdocs`. |
| **Page loads without styling** | The `public/build` folder didn't upload completely — upload it again. |
| **Product images missing** | Upload the `public/storage` folder again. |
| **Login form just reloads** | Clear your browser cookies for the site, and use `http://` until SSL is active. |
| **Order confirmation emails** | Free hosting blocks email, so emails are not sent (orders still work and appear in Admin → Orders). |

To see the exact error, temporarily set `APP_DEBUG=true` in `.env` on the server, reload the page,
read the message, then set it back to `false`.

## Updating the site later

After changes on your computer, run `scripts\build-infinityfree.ps1` again and upload only the changed
folders (for example `app`, `resources`, `routes`, `public/build`). **Do not re-import the database**
on the live site — that would overwrite real orders.
