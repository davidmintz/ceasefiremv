# Deploying the RSVP form to amiens

One-time steps for the first deploy of the RSVP feature. Run everything on
amiens from the app directory unless noted. Allow about 30 minutes.

## Before you start (locally / in a browser)

1. **Fastmail:** create an app password with SMTP access only, named something
   like "mvforpalestine amiens". Keep it handy for step 6.
2. **Push** the RSVP commit from lin-chi.

## On amiens

3. **SQLite for PHP** (dev and prod both use SQLite):

   ```
   sudo apt install php8.5-sqlite3        # match amiens's PHP version
   sudo systemctl reload php8.5-fpm       # or restart Apache, whichever serves PHP
   ```

   Check with `php -m | grep -i sqlite`.

4. **Update the code and dependencies:**

   ```
   git pull
   composer install --no-dev --optimize-autoloader
   ```

5. **Database:** `.env` now sets `DATABASE_URL` to `var/data.db`. If amiens's
   `.env.local` has an empty `DATABASE_URL=` line, delete it, or it will
   override the one in `.env`.

6. **Mail:** add this line to amiens's `.env.local`, using the new app password.
   The `@` in the username has to be written `%40`:

   ```
   MAILER_DSN="smtp://mintz%40vernontbludgeon.com:NEW_APP_PASSWORD@smtp.fastmail.com:587"
   ```

   (Moving this into a prod secrets vault can wait until after 17-Nov.)

7. **Create the tables:**

   ```
   APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction
   ```

8. **Permissions:** the web server user (usually `www-data`) must be able to write
   to `var/`, including `var/data.db`. SQLite needs write access to the directory too,
   not just the file.

   ```
   sudo chown -R www-data: var/
   ```

9. **Clear the cache.** This is required, because the migrations bundle was upgraded:

   ```
   APP_ENV=prod php bin/console cache:clear
   ```

   (and `asset-map:compile` if that's part of your usual deploy)

## Check it

10. **Mail test:** `APP_ENV=prod php bin/console app:mail-test` sends a test email to
    info@mvforpalestine.org. If it fails, re-check the DSN in step 6.
11. **Real run-through:** open https://mvforpalestine.org/rsvp, RSVP with your own address,
    click the button in the email, then Confirm. Expect the "Thank you" page.
12. **If something 500s:** look in `var/log/prod.log`.

## After the meeting

- Move `MAILER_DSN` into a prod secrets vault (`secrets:generate-keys --env=prod`,
  `secrets:set MAILER_DSN --env=prod`, then copy the private key to amiens yourself).
- Upgrade Symfony 7.0 to 7.4 LTS.
