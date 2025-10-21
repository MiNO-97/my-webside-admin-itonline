# POS IT Online - ລະບົບສັ່ງຊື້ອຸປະກອນ IT ອອນລາຍ

ລະບົບສັ່ງຊື້ອຸປະກອນ IT ທີ່ທັນສະໄໝ ແລະ ສະດວກສະບາຍ ສ້າງດ້ວຍ PHP ແລະ MySQL

## ✨ ຄຸນສົມບັດຫຼັກ

### 🛒 ສໍາລັບລູກຄ້າ
- **ການລົງທະບຽນແລະເຂົ້າສູ່ລະບົບ** - ລະບົບຄວາມປອດໄພທີ່ເຂັ້ມແຂງ
- **ການຊື້ສິນຄ້າ** - ເບິ່ງ, ຄົ້ນຫາ, ເພີ່ມກະຕ່າ, ສັ່ງຊື້
- **ການຈ່າຍເງິນ** - ຫຼາຍວິທີການຈ່າຍເງິນ
- **ການປະເມີນສິນຄ້າ** - ລະບຸຄະເນື່ອນແລະຄຳເຫັນ
- **ການຕິດຕາມຄໍາສັ່ງຊື້** - ສະຖານະຄໍາສັ່ງຊື້ແລະປະຫວັດ

### 👨‍💼 ສໍາລັບຜູ້ບໍລິຫານ
- **ຈັດການຂໍ້ມູນ** - ສິນຄ້າ, ໝວດໝູ່, ລູກຄ້າ, ພະນັກງານ
- **ການສັ່ງຊື້ສິນຄ້າ** - ສັ່ງຊື້ສິນຄ້າເຂົ້າຮ້ານ
- **ການຈັດການຄໍາສັ່ງຊື້** - ຕິດຕາມແລະອັບເດດສະຖານະ
- **ລາຍງານ** - ສະຖິຕິການຂາຍ, ສິນຄ້າຂາຍດີ, ລາຍໄດ້
- **ການຕັ້ງຄ່າລະບົບ** - ການຕັ້ງຄ່າທົ່ວໄປ

## 🚀 ການຕິດຕັ້ງ

### ຄວາມຕ້ອງການລະບົບ
- PHP 7.4 ຫຼື ສູງກວ່າ
- MySQL 5.7 ຫຼື ສູງກວ່າ
- Apache/Nginx web server
- XAMPP, WAMP, ຫຼື LAMP stack

### ຂັ້ນຕອນການຕິດຕັ້ງ

1. **Clone ຫຼື Download ໂປຣເຈັກ**
   ```bash
   git clone https://github.com/yourusername/pos-it-online.git
   cd pos-it-online
   ```

2. **ຕັ້ງຄ່າ Database**
   - ເປີດ phpMyAdmin ຫຼື MySQL client
   - ສ້າງ database ໃໝ່ຊື່ `pos_itonline`
   - Import ໄຟລ໌ `database/schema.sql`

3. **ຕັ້ງຄ່າການເຊື່ອມຕໍ່ Database**
   - ແກ້ໄຂໄຟລ໌ `config/database.php`
   - ປ່ຽນຂໍ້ມູນການເຊື່ອມຕໍ່ຕາມຄວາມຈໍາເປັນ

4. **ຕັ້ງຄ່າ Web Server**
   - Copy ໂປຣເຈັກໄປໃນ web root directory
   - ຕັ້ງຄ່າ permissions ສໍາລັບ uploads folder (ຖ້າມີ)

5. **ການເຂົ້າໃຊ້ລະບົບ**
   - **ລູກຄ້າ**: ເປີດ `http://localhost/pos-it-online`
   - **ຜູ້ບໍລິຫານ**: ເປີດ `http://localhost/pos-it-online/admin`

## 🔐 ບັນຊີທົດສອບ

### ຜູ້ບໍລິຫານ
- **Email**: admin@positonline.com
- **Password**: password

### ລູກຄ້າ
- ລົງທະບຽນບັນຊີໃໝ່ທີ່ໜ້າລົງທະບຽນ

## 📁 ໂຄງສ້າງໂປຣເຈັກ

```
pos-it-online/
├── admin/                 # ສ່ວນຜູ້ບໍລິຫານ
│   ├── dashboard.php     # ໜ້າຫຼັກຜູ້ບໍລິຫານ
│   ├── login.php         # ເຂົ້າສູ່ລະບົບຜູ້ບໍລິຫານ
│   ├── products.php      # ຈັດການສິນຄ້າ
│   ├── orders.php        # ຈັດການຄໍາສັ່ງຊື້
│   └── logout.php        # ອອກຈາກລະບົບ
├── ajax/                 # AJAX handlers
│   └── add_to_cart.php   # ເພີ່ມສິນຄ້າລົງກະຕ່າ
├── config/               # ການຕັ້ງຄ່າ
│   └── database.php      # ການເຊື່ອມຕໍ່ database
├── database/             # Database schema
│   └── schema.sql        # SQL schema
├── includes/             # Helper functions
│   └── functions.php     # ຟັງຊັນຊ່ວຍເຫຼືອ
├── uploads/              # ໄຟລ໌ທີ່ upload
├── index.php             # ໜ້າຫຼັກລູກຄ້າ
├── login.php             # ເຂົ້າສູ່ລະບົບລູກຄ້າ
├── register.php          # ລົງທະບຽນລູກຄ້າ
├── products.php          # ໜ້າສິນຄ້າ
├── cart.php              # ກະຕ່າສິນຄ້າ
├── checkout.php          # ການຊື້ສິນຄ້າ
└── README.md             # ຄູ່ມືການໃຊ້ງານ
```

## 🛠️ ເທັກໂນໂລຊີທີ່ໃຊ້

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL** - Database management
- **PDO** - Database abstraction layer

### Frontend
- **HTML5** - Markup language
- **CSS3** - Styling with Bootstrap 5
- **JavaScript** - Client-side functionality
- **Bootstrap 5** - Responsive UI framework
- **SweetAlert2** - Beautiful alerts
- **Chart.js** - Data visualization
- **Font Awesome** - Icons

### ຄຸນສົມບັດພິເສດ
- **Responsive Design** - ໃຊ້ໄດ້ທຸກອຸປະກອນ
- **Lao Language Support** - ຮອງຮັບພາສາລາວ
- **Modern UI/UX** - ການອອກແບບທີ່ທັນສະໄໝ
- **Security Features** - ຄວາມປອດໄພທີ່ເຂັ້ມແຂງ
- **Real-time Updates** - ການອັບເດດແບບ real-time

## 🔒 ຄວາມປອດໄພ

- **Password Hashing** - ໃຊ້ bcrypt ເພື່ອເຂົ້າລະຫັດລະຫັດຜ່ານ
- **SQL Injection Protection** - ໃຊ້ Prepared Statements
- **XSS Protection** - ການປ້ອງກັນ Cross-site scripting
- **CSRF Protection** - ການປ້ອງກັນ Cross-site request forgery
- **Session Security** - ການຈັດການ session ທີ່ປອດໄພ

## 📊 ຄຸນສົມບັດລາຍງານ

- **ສະຖິຕິການຂາຍ** - ຍອດຂາຍປະຈໍາວັນ/ເດືອນ/ປີ
- **ສິນຄ້າຂາຍດີ** - ສິນຄ້າທີ່ຂາຍດີທີ່ສຸດ
- **ລາຍໄດ້ລາຍຈ່າຍ** - ການຕິດຕາມລາຍໄດ້ແລະລາຍຈ່າຍ
- **ສະຖິຕິລູກຄ້າ** - ການວິເຄາະລູກຄ້າ
- **ການຄາດຄະເນ** - ການຄາດຄະເນຍອດຂາຍ

## 🚀 ການພັດທະນາຕໍ່

### ຄຸນສົມບັດທີ່ຈະເພີ່ມໃນອະນາຄົດ
- [ ] ການຈ່າຍເງິນອອນລາຍ
- [ ] ການສົ່ງ SMS/Email notifications
- [ ] Mobile app (React Native)
- [ ] Multi-language support
- [ ] Advanced analytics
- [ ] Inventory management
- [ ] Supplier management
- [ ] Barcode scanning

## 🤝 ການສະໜັບສະໜູນ

ຖ້າທ່ານພົບບັນຫາ ຫຼື ມີຄຳຖາມ ກະລຸນາສ້າງ issue ໃໝ່ໃນ GitHub repository.

## 📄 License

ໂປຣເຈັກນີ້ໃຊ້ MIT License. ເບິ່ງໄຟລ໌ LICENSE ສໍາລັບຂໍ້ມູນເພີ່ມເຕີມ.

## 👨‍💻 ຜູ້ພັດທະນາ

ສ້າງດ້ວຍ ❤️ ໂດຍ [ຊື່ຜູ້ພັດທະນາ]

---

**ຫມາຍເຫດ**: ລະບົບນີ້ສ້າງຂຶ້ນສໍາລັບການຮຽນຮູ້ແລະການພັດທະນາ. ກະລຸນາໃຊ້ໃນການຜະລິດດ້ວຍຄວາມລະມັດລະວັງ. 