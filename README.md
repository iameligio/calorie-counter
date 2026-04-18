# 👋 Welcome to the Calorie Counter!

Hello! If you're reading this, you're probably looking to get this calorie tracking app up and running on your own web host. 

This app is designed to be your personal health companion—it calculates your daily calorie goals perfectly tailored to your body and lifestyle (using the Mifflin-St Jeor formula), tracks your daily food logs, and gives you a beautiful "Weight Hub" to visually hit your target weight.

Because we wanted this app to be incredibly fast and secure, it's actually **two separate apps working together**:
1. **The Backend (Laravel)**: Think of this as the brain and the database. It stores the users, the food logs, and does the heavy math.
2. **The Frontend (React)**: Think of this as the beautiful face of the app. It's the buttons you click, the charts you see, and the dashboard you interact with.

To get this app live on the internet, you'll need to "host" both parts. Don't worry, it's easier than it sounds! Here is a friendly, step-by-step guide to doing just that.

---

## 🛠️ Step 1: Setting up the Laravel Brain (Backend)

We usually install the backend on a subdomain, like `api.yourwebsite.com`.

1. **Upload the Code:** Take the entire `backend` folder from this repository and upload it to your web server. 
   *(Pro-tip: If you're using cPanel, make sure your domain's "Document Root" points directly to the `backend/public` folder, not just the `backend` folder!)*
   
2. **Create the Keys to the Castle:** In that backend folder, you'll see a file called `.env.example`. Make a copy of it and rename the copy to just `.env`. Open it up and type in your live database credentials so Laravel can speak to your server's database.
   
3. **Connect the Dots:** Inside that same `.env` file, tell Laravel where it lives, and where its frontend companion will live:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://api.yourwebsite.com
   FRONTEND_URL=https://app.yourwebsite.com
   SANCTUM_STATEFUL_DOMAINS=app.yourwebsite.com
   ```
   
4. **Final Polish (SSH/Terminal):** Log into your server's terminal and run these three setup commands from inside the backend folder to install its tools and build the database tables:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan key:generate
   php artisan migrate --force --seed
   ```

Boom! 🧠 Your API brain is now live and waiting for instructions.

---

## 🎨 Step 2: Setting up the React Face (Frontend)

The frontend is just a collection of static files (HTML, CSS, JavaScript). We usually host this on your main domain, or a subdomain like `app.yourwebsite.com`.

1. **Tell React where the Brain is:** Before you prepare the files, go to your local `frontend` folder on your computer. Make a copy of `.env.example` and name it `.env`. Open it up and type in the URL of the Laravel API you just set up:
   ```env
   VITE_API_BASE_URL=https://api.yourwebsite.com/api
   ```

2. **Package it up:** We need to translate the React code into plain web files any browser can read. Run these commands on your computer inside the frontend folder:
   ```bash
   npm install
   npm run build
   ```
   
3. **Upload to the Web:** A new folder called `dist` will magically appear. Take **only the files inside the `dist` folder** and upload them to your web host where `app.yourwebsite.com` lives.

4. **The Final Trick (Routing):** Because React handles multiple pages (like `/login` or `/dashboard`) automatically, you need to tell your web server not to get confused if someone refreshes the page. 
   - If you use **Apache**, create a file named `.htaccess` in your `/dist` folder with this inside:
     ```apache
     <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteBase /
       RewriteRule ^index\.html$ - [L]
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule . /index.html [L]
     </IfModule>
     ```

And that's it! 🎉 You can now navigate to `https://app.yourwebsite.com` and use the application! 

### ⚙️ Need to adjust settings later?
We've included a powerful admin panel! Just navigate into your backend domain: `https://api.yourwebsite.com/admin` to easily edit system configurations and API limits.
