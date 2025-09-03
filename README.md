
# Caddy Web Manager (PHP)

แดชบอร์ดบริหาร Caddy ผ่าน Admin API ด้วย PHP (Apache) + Docker Compose

ไฟล์สำคัญ:
- `docker-compose.yml` – กำหนดบริการ `app` (เว็บ) และ `caddy`
- `Caddyfile` – กำหนด Admin API ของ Caddy
- `docker.env` – ค่า environment ของเว็บ (โหลดเป็น `.env` ภายในคอนเทนเนอร์)
- `pages/index.php` – หน้า Servers (มีปุ่ม "+ Add Server")

## คุณสมบัติหลัก
- ดู/เพิ่ม/แก้ไข/ลบ HTTP servers ใน Caddy (ผ่าน Admin API `/load`, `/config`)
- UI เรียบง่าย ใช้ DataTables + SweetAlert2
- โหมด Production แบบปลอดภัย: Admin API เปิดเฉพาะใน Docker network

## ความต้องการ
- Docker และ Docker Compose v2
- พอร์ตที่ใช้ภายนอก: 80, 443, 81 (หน้าเว็บแอป)

## เริ่มต้นแบบเร็ว (Quick Start)
1) ตรวจ/แก้ค่าต่อไปนี้
   - ไฟล์ `Caddyfile`:
     ```
     {
       admin 0.0.0.0:2019
     }
     ```
   - ไฟล์ `docker.env`:
     ```env
     WEB_MODE="production"
     WEB_URL="/"
     DB_DRIVER="sqlite"
     SQLITE_PATH="storage/database.sqlite"
     JWT_SECRET="change_me_in_production"
     CADDY_URL="http://caddy:2019"   # ใช้งานภายใน Docker network
     ```
   - ไฟล์ `docker-compose.yml`: ไม่มีการ publish พอร์ต `2019:2019`

2) ขึ้นระบบ
   ```bash
   docker compose up -d --build
   ```

3) เข้าใช้งานเว็บ
   - URL: `http://<SERVER-IP>:81/`
   - เมนู "Servers" จะเชื่อมกับ Caddy Admin API ภายใน

## การใช้งานหน้า Servers
- ปุ่ม "+ Add Server" จะเปิดโมดอลสร้าง server ใหม่
  - กรอก
    - Server Name: เช่น `srv0`
    - Port: รับ `:80`, `:443`, `8080`, หรือ `0.0.0.0:8080`
  - กด "บันทึก" ระบบจะ POST ด้วย `action=save_server` และเรียก Caddy `/load` ให้อัตโนมัติ
- ปุ่ม "Edit" จะเปิดโมดอลแก้ไขชื่อและพอร์ต (รองรับ rename)
- ปุ่ม "Delete" จะยืนยันด้วย SweetAlert2 แล้วบันทึกกลับไปที่ Caddy

หมายเหตุ: ถ้า `CADDY_URL` ชี้ไม่ถูก/ติดต่อไม่ได้ จะมีแจ้งเตือนผ่าน query `?status=error` บนหน้า

## โครงสร้างพอร์ต
- `app` (Apache): ภายนอก `81 -> 8000`
- `caddy`: ภายนอก `80:80`, `443:443` (Admin API 2019 ใช้ภายในเท่านั้น)

## คำสั่งที่พบบ่อย
```bash
# ดูสถานะคอนเทนเนอร์
docker compose ps

# ดูล็อก
docker compose logs -f app
docker compose logs -f caddy

# รีโหลด Caddy เมื่แก้ Caddyfile
docker compose exec caddy caddy reload --config /etc/caddy/Caddyfile

# ทดสอบ Admin API จากภายในคอนเทนเนอร์แอป
docker compose exec app curl -s http://caddy:2019/config/ | head
```

## เอกสารและลิงก์สำคัญ
- __API Docs (Swagger)__: เปิดที่หน้าเว็บ `http://<SERVER-IP>:81/swagger`
- __OpenAPI Spec (JSON)__: `http://<SERVER-IP>:81/api/readfile/other.php?file=openapi.json`
- __Caddy Server Documentation__: https://caddyserver.com/docs/
- __Caddy Admin API Reference__: https://caddyserver.com/docs/api

## Troubleshooting
- เปิดหน้าแล้วไม่เจอปุ่ม "Add Server"
  - ตรวจว่าไฟล์ `pages/index.php` อัปเดตแล้ว (มีปุ่มในส่วนหัว) และรีเฟรชเคลียร์ cache
- สร้าง/แก้ไข server แล้วขึ้น error
  - ตรวจ `CADDY_URL` ใน `docker.env` ว่าตั้งเป็น `http://caddy:2019`
  - รัน `docker compose exec app curl -v http://caddy:2019/config/ | head` ควรได้ HTTP 200 + JSON
- เข้าถึง `http://34.x.x.x:2019` จากภายนอกไม่ได้
  - ตั้งใจให้ปิดเพื่อความปลอดภัย (ไม่ publish 2019) ให้เรียกภายในผ่าน `caddy:2019` เท่านั้น

## นักพัฒนา (Development tips)
- โค้ดหลักฝั่ง PHP อยู่ใน:
  - `pages/` – เพจและ UI
  - `controllers/`, `includes/`, `composables/` – โครงสร้างแอป
- หากแก้ dependency ให้รันในคอนเทนเนอร์ (entrypoint จะจัดการ autoload ให้):
  ```bash
  docker compose exec app composer install
  docker compose exec app composer dump-autoload -o
  ```

---
 
# Caddy Web Manager (PHP) — English
 
Manage Caddy via the Admin API using PHP (Apache) + Docker Compose.
 
Important files:
- `docker-compose.yml` – defines `app` (web) and `caddy` services
- `Caddyfile` – configures Caddy Admin API
- `docker.env` – environment values for the web app (loaded as `.env` inside the container)
- `pages/index.php` – Servers page (with "+ Add Server" button)
 
## Features
- View/Create/Update/Delete HTTP servers in Caddy (via Admin API `/load`, `/config`)
- Simple UI using DataTables + SweetAlert2
- Safe production mode: Admin API is only exposed inside the Docker network
 
## Requirements
- Docker and Docker Compose v2
- External ports used: 80, 443, 81 (web app UI)
 
## Quick Start
1) Verify/update these values
   - `Caddyfile`:
     ```
     {
       admin 0.0.0.0:2019
     }
     ```
   - `docker.env`:
     ```env
     WEB_MODE="production"
     WEB_URL="/"
     DB_DRIVER="sqlite"
     SQLITE_PATH="storage/database.sqlite"
     JWT_SECRET="change_me_in_production"
     CADDY_URL="http://caddy:2019"   # used inside the Docker network
     ```
   - `docker-compose.yml`: do not publish `2019:2019`
 
2) Start the stack
   ```bash
   docker compose up -d --build
   ```
 
3) Open the web UI
   - URL: `http://<SERVER-IP>:81/`
   - The "Servers" menu talks to the internal Caddy Admin API
 
## Using the Servers page
- The "+ Add Server" button opens a modal to create a server
  - Fields
    - Server Name: e.g. `srv0`
    - Port: accepts `:80`, `:443`, `8080`, or `0.0.0.0:8080`
  - Click "Save": it will POST `action=save_server` and call Caddy `/load`
- "Edit" opens a modal to change name/port (rename supported)
- "Delete" confirms via SweetAlert2 and writes back to Caddy
 
Note: If `CADDY_URL` is wrong/unreachable, the page will show `?status=error`.
 
## Port mapping
- `app` (Apache): external `81 -> 8000`
- `caddy`: external `80:80`, `443:443` (Admin API 2019 is internal only)
 
## Common commands
```bash
# Container status
docker compose ps
 
# Logs
docker compose logs -f app
docker compose logs -f caddy
 
# Reload Caddy after editing Caddyfile
docker compose exec caddy caddy reload --config /etc/caddy/Caddyfile
 
# Test Admin API from inside the app container
docker compose exec app curl -s http://caddy:2019/config/ | head
```
 
## Docs & links
- API Docs (Swagger): open at `http://<SERVER-IP>:81/swagger`
- OpenAPI Spec (JSON): `http://<SERVER-IP>:81/api/readfile/other.php?file=openapi.json`
- Caddy Server Documentation: https://caddyserver.com/docs/
- Caddy Admin API Reference: https://caddyserver.com/docs/api
 
## Troubleshooting
- "Add Server" button missing
  - Ensure `pages/index.php` is updated (contains the button) and hard refresh to clear cache
- Errors when creating/editing a server
  - Check `CADDY_URL` in `docker.env` is `http://caddy:2019`
  - Run `docker compose exec app curl -v http://caddy:2019/config/ | head` and expect HTTP 200 + JSON
- Cannot access `http://34.x.x.x:2019` externally
  - This is intentional for security (do not publish 2019); access internally via `caddy:2019` only
 
## For developers
- Main PHP code:
  - `pages/` – pages & UI
  - `controllers/`, `includes/`, `composables/` – app structure
- If you change dependencies, run inside the container (entrypoint handles autoload):
  ```bash
  docker compose exec app composer install
  docker compose exec app composer dump-autoload -o
  ```
 
## ผู้เขียน
- [@WEBDERNargor](https://github.com/WEBDERNargor)
