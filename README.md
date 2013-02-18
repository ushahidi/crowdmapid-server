# CrowdmapID

## Synopsis
CrowdmapID is a simple authentication and identity management system that provides users with a secure central sign-on facility.

## Requirements
Built for PHP 5.3+. Requires a MySQL 5.1+ and Memcached server. You can optionally send mail using the Sendgrid.com API, if you have an account.

## API Versions
CrowdmapID currently supports the v1.0 API, which is backwards compatible with RiverID.

## Installation
1. Create a MySQL database.
2. Run the `create_database.sql` file on the database.
3. Copy the contents of `config.example.php` and paste it into a new file called `config.php`
4. Modify the relevant settings.
5. Add an application to the database, since all calls must have an accompanying API key. Here's an example:
```sql
INSERT INTO `applications` (`id`, `name`, `url`, `secret`, `ratelimit`, `mail_from`, `note`, `admin_email`, `admin_identity`, `debug`, `registered`, `admin_access`)
VALUES
	(1, 'App Name', 'https://appurl.com', 'B07E6009296E90C0ABE480B828241212300172B10CA60B8B0063BAD56C1B2222', 0, 'some@email.com', '', 'some@email.com', 'Some Company Name', 0, NOW(), 1);
```
The important field here is the secret key. Once this is in the table, you can hit your new API. Example URL: http://newapisiteurl.com/?api_secret=B07E6009296E90C0ABE480B828241212300172B10CA60B8B0063BAD56C1B2222

## Methods
TBA