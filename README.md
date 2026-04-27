# Midnight-help
A community-driven emergency support system where users can request non-police emergency help (vehicle breakdown, medical help, night travel assistance).

## Database

The schema is in `db/schema.sql` (compatible with XAMPP/MariaDB).

### Run it with XAMPP (recommended)

- Start **MySQL** in the XAMPP Control Panel
- Open `http://localhost/phpmyadmin`
- Go to **Import** → choose `db/schema.sql` → **Go**

## Backend (PHP + XAMPP)

Login/registration uses PHP endpoints in `api/` and requires serving the site from XAMPP.

- Copy this folder to: `C:\xampp\htdocs\Midnight-help`
- Open the site at: `http://localhost/Midnight-help/index.html`

If your XAMPP MySQL has a password, update it in `api/db.php`.

### Run it via MySQL CLI (if you have it)

```sql
SOURCE db/schema.sql;
```
