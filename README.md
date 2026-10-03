# Smart Examination Attendance System (PHP & MySQL)

Nidaam dhameystiran oo loogu talagalay maamulka iyo xaqiijinta imaanshaha imtixaanaadka jaamacadaha ama kulliyadaha (Smart Examination Attendance System), ku shaqeynaya **PHP**, **MySQL**, **Tailwind CSS**, iyo **Vanilla JavaScript**.

---

## 🌟 Tilmaamaha Muhiimka ah (Key Features)

1. **Xaqiijinta Imaanshaha (Live ID & QR Scanning):**
   - Baaritaanka ardayda iyadoo la isticmaalayo Student ID ama QR Code.
   - Xaqiijinta tooska ah ee u-qalmitaanka (Eligibility verification):
     - Hubinta Kulliyadda (Faculty) iyo Qeybta (Department).
     - Hubinta Kharashka Waxbarashada (Fee Status Clearance).
     - Hubinta Maqnaanshaha (Absence Threshold check).
2. **Doorka Isticmaalayaasha (Role-Based Access Control):**
   - **Admin:** Maamulidda ardayda, imtixaanaadka, kormeerayaasha, kulliyadaha, iyo warbixinnada.
   - **Invigilator (Kormeere):** Qaadista xaadiriska imtixaanka loo xilsaaray iyo xaqiijinta ardayda.
3. **Maamulka Imtixaanaadka (Examination Management):**
   - Jadwalka imtixaanaadka, qolalka (Rooms), waqtiyada (Time slots), iyo u-xilsaaridda kormeerayaasha.
4. **Offline Mode & Auto-Sync:**
   - Awood u leh in offline lagu shaqeeyo haddii internet-ku go'o lana keydiyo imaanshaha, ka dibna la isku waafajiyo (sync) markii xariirku soo noqdo.
5. **Warbixinnada & Dhoofinta (Reports & Export):**
   - Warbixinno faahfaahsan oo ku saabsan imaanshaha, ardayda xaadirtay, kuwa laga reebay, iyo dhoofinta xogta (CSV/Print).
6. **Auto Database Setup:**
   - System-ku marka la furo markiiba wuxuu si toos ah u sameynayaa database-ka `exam_attendance_db` iyo xogtiisii aasaasiga ahayd (auto-seeding).

---

## 💻 Shuruudaha Nidaamka (Requirements)

- **PHP**: 7.4 ama 8.0+
- **MySQL / MariaDB**: 5.7+ ama 8.0+
- **Server**: XAMPP, WampServer, Laragon, ama LAMP/LEMP

---

## 🚀 Sida Loo Rakibo (Installation Guide)

1. **Soo Deji ama Clone garee Project-ka:**
   Mashruuca dhig galka `htdocs` ee XAMPP:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/anasabdiwahid/Php-Attendance.git attendance
   ```

2. **Bixi Server-ka (Start Apache & MySQL):**
   - Fur **XAMPP Control Panel**.
   - Start dheh **Apache** iyo **MySQL**.

3. **Fur Bogga Web-ka:**
   - Fur browser-kaaga oo booqo:
     ```
     http://localhost/attendance/
     ```
   - *Fiiro gaar ah: Nidaamku wuxuu si toos ah u abuurayaa database-ka `exam_attendance_db` marka ugu horeysa ee aad gasho.*
   - Haddii aad rabto inaad gacanta ku shubato database-ka, waxaad isticmaali kartaa faylka `database.sql` adigoo gelinaya phpMyAdmin.

---

## 🔑 Akoonnada Tijaabada (Default Credentials)

| Role | Username | Password |
|---|---|---|
| **Admin** | `admin1` | `Admin@123` |
| **Admin** | `admin2` | `Admin@123` |
| **Invigilator** | `invig1` | `Invigilator@123` |
| **Invigilator** | `invig2` | `Invigilator@123` |
| **Invigilator** | `invig3` | `Invigilator@123` |

### IDs-ka Ardayda Tijaabada:
- `HU2001` - Hodan Ibrahim (Eligible)
- `HU2002` - Khalid Omar (Eligible)
- `HU2003` - Ifrah Abdullahi (Fee Not Cleared - Not Eligible)
- `HU3002` - Cabdiraxmaan Sheikh (Absence > 3 - Not Eligible)

---

## 📁 Qaab-dhismeedka Mashruuca (Project Structure)

```
attendance/
├── api/
│   ├── get_roster.php        # API u soo saara liiska ardayda imtixaanka
│   └── scan_check.php        # API xaqiijiya xaadiriska & u-qalmitaanka
├── config/
│   └── db.php                # Xiriirka MySQL, abuurista tables-ka & functions
├── includes/
│   ├── header.php            # Navbar, Sidebar & layout madaxa
│   └── footer.php            # Script-yada & layout hoose
├── assignments.php           # U-xilsaaridda kormeerayaasha
├── classes.php               # Maamulka fasallada
├── database.sql              # Database schema & sample seed data
├── exams.php                 # Maamulka imtixaanaadka
├── faculties.php             # Maamulka kulliyadaha & qeybaha
├── index.php                 # Dashboard-ka guud
├── login.php                 # Bogga gelitaanka (Login)
├── logout.php                # Ka bixitaanka akoonka
├── reports.php               # Warbixinnada & tirakoobyada
├── scan.php                  # Bogga xaadiriska & scanner-ka
├── students.php              # Maamulka ardayda
├── users.php                 # Maamulka isticmaalayaasha
└── README.md                 # Macluumaadka mashruuca
```

---

## 👤 Qoraaga (Author)

- **Anas Abdiwahid**
- GitHub: [@anasabdiwahid](https://github.com/anasabdiwahid)
