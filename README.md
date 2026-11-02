# Music Player

## Run locally with XAMPP

Start Apache and MySQL/MariaDB in XAMPP. Put this repository in `htdocs/decibel`
(Linux: `/opt/lampp/htdocs/decibel`; Windows: `C:\xampp\htdocs\decibel`).
Import `sql/schema.sql` into MariaDB using phpMyAdmin, then open
`http://localhost/decibel/`. **The schema import drops and recreates `music_player`;
use it for initial setup only, or back up existing data first.**

The default connection in `includes/db.php` uses `127.0.0.1:3306`, database
`music_player`, username `root`, and an empty password. Local installations can
override these using `MUSIC_DB_HOST`, `MUSIC_DB_PORT`, `MUSIC_DB_NAME`,
`MUSIC_DB_USER`, and `MUSIC_DB_PASSWORD` environment variables. Restart Apache
after changing its environment. PHP needs the `pdo_mysql` extension.

### Accounts and dashboards

- Register an account at `register.php`; public registration always creates a listener.
- Listeners go to `listener/index.php`; administrators go to `admin/index.php`.
- `profile.php` shows only the signed-in account's information, including optional
  contact details. The admin dashboard also lists registered users.
- Dashboard permissions are checked against the database on every request.
  Logout uses a POST form with a CSRF token, as do login and registration.
- `welcome.php` remains the common entry point and redirects to the appropriate dashboard.

To create your first administrator, register normally, then run this SQL in
phpMyAdmin's `music_player` database, replacing `your_username`:

```sql
UPDATE users u
JOIN credentials c ON c.user_id = u.user_id
JOIN roles r ON r.role_type = 'admin'
SET u.role_id = r.role_id
WHERE c.username = 'your_username';
```

Refresh the app or log in again to reach the admin dashboard. There are no
preconfigured passwords or public admin registration. The application uses the
existing `listener` and `admin` roles; guest accounts are not part of this schema.

The dashboard layout, account information, and admin directory are adapted from
Joshua Connor's CSC235 classroom login example. The application keeps Kofi's PDO
queries, password hashing, registration transaction, and music-player schema.
`csc235_login/` remains a separate reference and is not required to run the app.
Password recovery and music playback are not implemented yet.

### Authentication checks

With PHP CLI and MariaDB running, run `php tests/auth_flow.php` from the repository.
It creates and removes a uniquely named test database and starts temporary PHP
servers on loopback. It never imports the schema into your `music_player` database.
The test database user must have permission to create and drop a database.


# Project Timeline: Decibel Music Player

---

| Date Window | Phase / Milestone | Deliverables & Scope |
| :--- | :--- | :--- |
| **Oct 12 – Oct 14** | **Project Proposal** | Finalize project scope, team roles, requirements specification, and architecture pitch. |
| **Oct 15 – Oct 28** | **Database & Auth Setup** | Initialize Git repository, design relational schema (Users, Songs, Playlists, Favorites), and implement authentication flows (login, register, reset password). |
| **Oct 29 – Nov 15** | **Core Player & Features Implementation** | Implement audio playback engine (play, pause, skip, seek, volume control), user library storage, and catalog search. |
| **Nov 15 – Nov 25** | **Admin Dashboard & UI Polishing** | Build administrative CRUD tools for catalog management (metadata and audio tracks) and refine frontend responsiveness and styling. |
| **Nov 25 – Nov 30** | **Testing & Final Polishing** | Comprehensive integration testing, bug fixes, edge-case handling, and final UI adjustments. |
| **Dec 02** | **Going Live & Final Presentation** | Final production deployment, live software demonstration, and system architecture presentation. |

---


## [Google Slides Link](https://docs.google.com/presentation/d/178jxpI_u8MC1a92iQgB96laRj2GbqkLb/edit?usp=sharing&ouid=112873102160782668801&rtpof=true&sd=true)

----
## [Lucid chart ER diagram link](https://lucid.app/lucidchart/5cfc838e-65fc-4b46-afa9-5efa8c884630/edit?viewport_loc=-264%2C-143%2C2384%2C1210%2C0_0&invitationId=inv_f4644d44-e86a-4129-91f9-fd2f0cf8084d)
