/**
 * NurSync - Bilingual Language Support (English / العربية)
 * 100% Client-side, decoupled and safe translation engine.
 */

const translations = {
    en: {
        // Navigation
        "nav_activities": "Activities",
        "nav_bulletin": "Periodical Bulletin",
        "nav_approvals": "Requests & Approvals",
        "nav_profile": "Profile",
        "nav_manage_attendees": "Management",
        "nav_login": "Login",
        "nav_logout": "Logout",
        "nav_home": "Home",
        "nav_portal_hub": "Nursing Hub Portal",
        
        // Brand & Subtitles
        "brand_subtitle": "Alahsa Health Cluster",
        "brand_tagline": "The unified hub for nursing education, training schedules, and healthcare activities.",
        "hero_welcome": "Welcome to NurSync",
        "hero_join_btn": "Join Activity",
        
        // Language Toggle Button Text
        "lang_btn_text": "العربية",
        
        // Home / Calendar Page
        "calendar_title": "Activities Calendar",
        "calendar_legend_title": "Hospital Key",
        "filter_hospital_all": "All Hospitals",
        "filter_hospital_ahc": "Health Cluster (AHC)",
        "filter_hospital_phc": "Primary Health Center (PHC)",
        "filter_hospital_kfgh": "King Faisal Gen. (KFGH)",
        "filter_hospital_kfhh": "King Fahad (KFHH)",
        "filter_hospital_pscc": "Prince Sultan Cardiac (PSCC)",
        "filter_hospital_psbj": "Prince Saud Bin Jalawy (PSBJ)",
        "filter_hospital_mch": "Maternity & Children (MCH)",
        "filter_hospital_mhh": "Mental Health (MHH)",
        "filter_hospital_oyun": "Oyun Hospital",
        "filter_hospital_jgh": "Aljafar General (JGH)",
        "filter_hospital_ogh": "Alomran General (OGH)",
        "filter_hospital_alafaleq": "Alafaleq Hospital",
        "filter_hospital_bhdc": "Blood Hereditary Center (BHDC)",
        
        "today_activities_title": "Today's Activities",
        "no_activities_today": "No activities scheduled for today.",
        "upcoming_activities": "Upcoming Activities",
        "view_all_activities": "View All Activities",
        "btn_add_activity": "Add Activity",
        "btn_delete_activity": "Delete Activity",
        
        // Days of Week
        "day_sun": "Sun",
        "day_mon": "Mon",
        "day_tue": "Tue",
        "day_wed": "Wed",
        "day_thu": "Thu",
        "day_fri": "Fri",
        "day_sat": "Sat",
        
        // Activities Page
        "page_activities_title": "Educational Activities",
        "page_activities_subtitle": "Browse and register for nursing training courses and workshops.",
        "filter_by_label": "Filter by:",
        "filter_all_months": "All Months",
        "stat_upcoming": "Upcoming",
        "stat_today": "Today",
        "stat_completed": "Completed",
        "stat_hospitals": "Hospitals",
        "stat_registered": "Joined",
        "search_placeholder": "Search activities by title, hospital, or instructor...",
        "filter_all": "All",
        "filter_upcoming": "Upcoming",
        "filter_today": "Today",
        "filter_past": "Past",
        "btn_register": "Request to Join",
        "btn_registered": "Requested",
        "btn_closed": "Registration Closed",
        "btn_details": "Details",
        "btn_view_cert": "Certificate",
        "btn_poster": "Poster",
        "btn_no_poster": "No Poster",
        "badge_today": "Today",
        
        // Join / Registration Modal
        "modal_join_title": "Register for Activity",
        "modal_full_name": "Full Triple Name *",
        "modal_full_name_ph": "e.g. Abdullah Mohammed Al-Ali",
        "modal_email": "Email Address *",
        "modal_phone": "Phone / WhatsApp *",
        "modal_hospital": "Your Hospital / Sector *",
        "modal_submit_join": "Confirm Registration",
        "modal_close": "Close",
        
        // Modals - General & Add Activity
        "modal_add_act_title": "Add New Activity",
        "modal_core_info": "Core Information",
        "modal_act_hosp": "Hospital",
        "modal_act_title": "Activity Title",
        "modal_title_ph": "e.g. Advanced Cardiac Life Support",
        "modal_scheduling": "Scheduling",
        "modal_act_date": "Activity Date",
        "modal_date_ph": "Select Date",
        "modal_act_time": "Start Time",
        "modal_time_ph": "Select Time",
        "modal_content_media": "Content & Media",
        "modal_poster_label": "Event Poster (Optional JPG/PNG)",
        "modal_slides_label": "Presentation Slides (Optional PDF/PPTX)",
        "modal_choose_img": "Choose Image",
        "modal_choose_file": "Choose File",
        "modal_no_file": "No file chosen",
        "modal_act_capacity": "Capacity (Seats)",
        "modal_act_location": "Hall / Room / Online Link",
        "modal_act_desc": "Description & Outline",
        "modal_act_instructor": "Instructor / Speaker",
        "modal_act_hours": "CME / Training Hours",
        "modal_save": "Save Activity",
        "modal_cancel": "Cancel",
        "modal_delete_title": "Delete Activity",
        "modal_select_delete": "Select an activity to delete...",
        "modal_confirm_delete": "Delete",
        
        // Bulletin Page
        "bulletin_title": "Periodical Nursing Bulletin",
        "bulletin_subtitle": "Official circulars, monthly newsletters, and administrative guidelines.",
        "bulletin_latest": "Published Bulletin",
        "bulletin_empty_title": "No Active Periodical Bulletin",
        "bulletin_empty_desc": "No new periodical bulletins have been published yet. Once the administration uploads a new bulletin, it will appear here.",
        "bulletin_upload_title": "Upload New Bulletin",
        "bulletin_upload_desc": "Publish a new PDF periodical bulletin to keep all medical cluster staff updated. The uploaded file will be displayed instantly to everyone.",
        "bulletin_drag_text": "Drag & Drop your PDF bulletin here, or click to browse",
        "bulletin_drag_sub": "Only PDF files are supported (Max 15MB)",
        "bulletin_upload_btn": "Upload Publication",
        "bulletin_download_btn": "Open / Download PDF",
        "bulletin_delete_btn": "Delete Current Bulletin",
        
        // Approvals Page
        "approvals_title": "Account Approvals",
        "approvals_subtitle": "Review and approve new coordinator registrations.",
        "tab_pending": "Pending",
        "tab_approved": "Approved",
        "tab_rejected": "Rejected",
        "tab_all": "All",
        "approval_status_pending": "Pending Approval",
        "approval_status_approved": "Approved",
        "approval_status_rejected": "Rejected",
        "empty_pending_accounts": "No pending accounts. You are all caught up!",
        
        // Manage Attendees Page
        "manage_title": "Attendee Management",
        "manage_subtitle": "Review registrant rosters, approve or decline requests, and export lists.",
        "tab_hosted_attendees": "Hosted Activity Attendees",
        "tab_reg_requests": "Registration Requests",
        "th_name": "Attendee Name",
        "th_contact": "Contact",
        "th_registered_at": "Registered At",
        "th_hospital": "Hospital",
        "th_email": "Email",
        "th_phone": "Phone",
        "th_status": "Status",
        "th_actions": "Actions",
        "th_participant": "Participant",
        "th_requested_activity": "Requested Activity",
        "th_activity_date": "Activity Date",
        "th_approvals_status": "Approvals Status",
        "btn_approve": "Approve",
        "btn_reject": "Decline",
        "btn_export": "Export to Excel",
        "empty_reg_requests": "All caught up! No pending registration requests.",
        
        // Profile Page
        "profile_title": "Nurse Profile",
        "profile_subtitle": "Your registered credentials and assigned health cluster facility.",
        "lbl_full_name": "Full Name",
        "lbl_email": "Email Address",
        "lbl_phone": "Phone Number",
        "lbl_hospital": "Assigned Hospital",
        "lbl_role": "Role / Rank",
        "btn_back_home": "Back to Dashboard",
        "btn_edit_profile": "Edit Profile",
        "btn_change_pw": "Change Password",
        "btn_back": "Back",
        "profile_note": "This information is managed by the Health Cluster Administration.",
        "role_coordinator": "Hospital Coordinator",
        "role_admin": "System Admin",
        "role_manager": "General Manager",
        
        // Login & Signup Pages
        "login_title": "Welcome Back",
        "login_subtitle": "For Hospital Coordinators Only",
        "login_email": "Email Address",
        "login_password": "Password",
        "login_remember": "Remember me",
        "login_forgot": "Forgot password?",
        "login_btn": "Sign In",
        "login_coordinator_btn": "Coordinator Sign Up",
        "login_no_account": "Don't have a coordinator account?",
        "login_register_here": "Register here",
        "login_create_one": "Register here",
        "login_back_home": "Back to Home",
        "brand_login_desc": "Connecting nurses with education, learning opportunities, and professional development across Alahsa Health Cluster.",
        
        "signup_title": "New Coordinator Registration",
        "signup_subtitle": "Create an account to manage activities for your hospital",
        "signup_name": "Full Name",
        "signup_name_ph": "e.g. Sarah Ahmad",
        "signup_email": "Email Address (Work/Personal)",
        "signup_email_ph": "name@example.com",
        "signup_phone": "Mobile Number",
        "signup_phone_ph": "05xxxxxxxx",
        "signup_hospital": "Assigned Hospital",
        "signup_hospital_default": "-- Select Your Hospital --",
        "signup_password": "Create Password",
        "signup_password_ph": "Min 8 chars, letters & numbers",
        "signup_confirm_pw": "Confirm Password",
        "signup_confirm_pw_ph": "Re-enter password",
        "signup_pw_rules": "Password must be at least 8 characters long and contain both letters and numbers.",
        "signup_btn": "Create Account",
        "signup_has_account": "Already have an account?",
        "signup_have_account": "Already have an account?",
        "signup_login_link": "Sign in here",
        "signup_login_here": "Sign in here",
        "signup_back_home": "Back to Home",
        
        // Password Rules Box
        "pw_rule_title": "Password must contain:",
        "pw_rule_length": "At least 8 characters",
        "pw_rule_letter": "At least one letter (a-z, A-Z)",
        "pw_rule_number": "At least one number (0-9)",
        
        // Hero & Sidecards
        "brand_login_title": "Nursing Excellence",
        "brand_signup_title": "Join Cluster Coordinators",
        "brand_signup_desc": "Create your coordinator account to manage cluster activities and resources efficiently across Alahsa.",
        
        // Footer
        "footer_rights": "All Rights Reserved - Alahsa Health Cluster Nursing Administration",
        "footer_developed": "Designed & Developed for Healthcare Excellence",

        // Profile & Approvals Workflow
        "btn_edit_profile": "Edit Profile",
        "btn_change_pw": "Change Password",
        "btn_back": "Back",
        "lbl_full_name": "Full Name",
        "lbl_email": "Email Address",
        "lbl_phone": "Phone Number",
        "lbl_hospital": "Assigned Hospital",
        "pw_current": "Current Password",
        "pw_new": "New Password",
        "pw_confirm": "Confirm New Password",
        "btn_update_pw": "Update Password",
        "profile_note": "This information is managed by the Health Cluster Administration.",
        "profile_pending_alert": "Your profile update request is currently under review by the Health Cluster (AHC).",
        "profile_pending_badge": "Update Pending Approval",
        "badge_new_account": "New Account",
        "badge_profile_update": "Profile Update",
        "lbl_requested_changes": "Requested Changes",
        "lbl_current_data": "Current",
        "lbl_new_data": "Requested",
        "toast_profile_submitted": "Your profile update request has been submitted for AHC approval.",
        "toast_profile_approved": "Profile update has been approved successfully.",
        "toast_profile_rejected": "Profile update has been rejected."
    },
    ar: {
        // Navigation
        "nav_activities": "الأنشطة التعليمية",
        "nav_bulletin": "النشرة الدورية",
        "nav_approvals": "الطلبات والاعتمادات",
        "nav_profile": "الملف الشخصي",
        "nav_manage_attendees": "إدارة الحضور",
        "nav_login": "تسجيل الدخول",
        "nav_logout": "تسجيل الخروج",
        "nav_home": "الرئيسية",
        "nav_portal_hub": "بوابة التمريض الموحدة",
        
        // Brand & Subtitles
        "brand_subtitle": "تجمع الأحساء الصحي",
        "brand_tagline": "المنصة الموحدة للتعليم التمريضي، جداول التدريب، والأنشطة الصحية المستمرة.",
        "hero_welcome": "مرحباً بكم في NurSync",
        "hero_join_btn": "التسجيل في نشاط",
        
        // Language Toggle Button Text
        "lang_btn_text": "English",
        
        // Home / Calendar Page
        "calendar_title": "تقويم الأنشطة التعليمية",
        "calendar_legend_title": "دليل ألوان المستشفيات",
        "filter_hospital_all": "جميع المستشفيات والقطاعات",
        "filter_hospital_ahc": "إدارة التجمع الصحي (AHC)",
        "filter_hospital_phc": "مراكز الرعاية الأولية (PHC)",
        "filter_hospital_kfgh": "مستشفى الملك فيصل العام (KFGH)",
        "filter_hospital_kfhh": "مستشفى الملك فهد بالهفوف (KFHH)",
        "filter_hospital_pscc": "مركز الأمير سلطان للقلب (PSCC)",
        "filter_hospital_psbj": "مستشفى الأمير سعود بن جلوي (PSBJ)",
        "filter_hospital_mch": "مستشفى الولادة والأطفال (MCH)",
        "filter_hospital_mhh": "مستشفى الصحة النفسية (MHH)",
        "filter_hospital_oyun": "مستشفى العيون",
        "filter_hospital_jgh": "مستشفى الجفر العام (JGH)",
        "filter_hospital_ogh": "مستشفى العمران العام (OGH)",
        "filter_hospital_alafaleq": "مستشفى العفالق للرعاية التأهيلية",
        "filter_hospital_bhdc": "مركز أمراض الدم الوراثية (BHDC)",
        
        "today_activities_title": "أنشطة اليوم",
        "no_activities_today": "لا توجد أنشطة مجدولة اليوم.",
        "upcoming_activities": "الأنشطة القادمة",
        "view_all_activities": "عرض جميع الأنشطة",
        "btn_add_activity": "إضافة نشاط",
        "btn_delete_activity": "حذف نشاط",
        
        // Days of Week
        "day_sun": "الأحد",
        "day_mon": "الإثنين",
        "day_tue": "الثلاثاء",
        "day_wed": "الأربعاء",
        "day_thu": "الخميس",
        "day_fri": "الجمعة",
        "day_sat": "السبت",
        
        // Activities Page
        "page_activities_title": "الأنشطة التعليمية والتدريبية",
        "page_activities_subtitle": "تصفح البرامج والورش التدريبية التمريضية وسجل حضورك بسهولة.",
        "filter_by_label": "تصفية حسب:",
        "filter_all_months": "جميع الأشهر",
        "stat_upcoming": "قادمة",
        "stat_today": "اليوم",
        "stat_completed": "منتهية",
        "stat_hospitals": "المستشفيات",
        "stat_registered": "المسجلين",
        "search_placeholder": "ابحث باسم النشاط، المستشفى، أو المدرب...",
        "filter_all": "الكل",
        "filter_upcoming": "القادمة",
        "filter_today": "اليوم",
        "filter_past": "السابقة",
        "btn_register": "طلب التسجيل",
        "btn_registered": "تم الطلب",
        "btn_closed": "التسجيل مغلق",
        "btn_details": "التفاصيل",
        "btn_view_cert": "الشهادة",
        "btn_poster": "الإعلان",
        "btn_no_poster": "لا يوجد إعلان",
        "badge_today": "اليوم",
        
        // Join / Registration Modal
        "modal_join_title": "التسجيل في النشاط التعليمي",
        "modal_full_name": "الاسم الثلاثي *",
        "modal_full_name_ph": "مثال: عبدالله محمد العلي",
        "modal_email": "البريد الإلكتروني *",
        "modal_phone": "رقم الجوال / الواتساب *",
        "modal_hospital": "المستشفى أو القطاع التابع له *",
        "modal_submit_join": "تأكيد طلب التسجيل",
        "modal_close": "إغلاق",
        
        // Modals - General & Add Activity
        "modal_add_act_title": "إضافة نشاط جديد",
        "modal_core_info": "المعلومات الأساسية",
        "modal_act_hosp": "المستشفى",
        "modal_act_title": "عنوان النشاط",
        "modal_title_ph": "مثال: دورة الإنعاش القلبي الرئوي",
        "modal_scheduling": "الموعد والجدول الزمني",
        "modal_act_date": "تاريخ النشاط",
        "modal_date_ph": "اختر التاريخ",
        "modal_act_time": "وقت البدء",
        "modal_time_ph": "اختر الوقت",
        "modal_content_media": "المحتوى والوسائط",
        "modal_poster_label": "بوستر الإعلان (اختياري JPG/PNG)",
        "modal_slides_label": "شرائح العرض (اختياري PDF/PPTX)",
        "modal_choose_img": "اختيار صورة",
        "modal_choose_file": "اختيار ملف",
        "modal_no_file": "لم يتم اختيار ملف",
        "modal_act_capacity": "السعة (عدد المقاعد)",
        "modal_act_location": "القاعة / الغرفة / رابط الأونلاين",
        "modal_act_desc": "الوصف ومحاور النشاط",
        "modal_act_instructor": "المدرب / المتحدث",
        "modal_act_hours": "الساعات التعليمية (CME)",
        "modal_save": "حفظ النشاط",
        "modal_cancel": "إلغاء",
        "modal_delete_title": "حذف نشاط تعليمي",
        "modal_select_delete": "اختر النشاط المراد حذفه...",
        "modal_confirm_delete": "حذف نهائي",
        
        // Bulletin Page
        "bulletin_title": "النشرة التمريضية الدورية",
        "bulletin_subtitle": "التعاميم الرسمية، النشرات الإخبارية الشهرية، واللوائح الإدارية.",
        "bulletin_latest": "النشرة المنشورة",
        "bulletin_empty_title": "لا توجد نشرة دورية حالياً",
        "bulletin_empty_desc": "لم يتم نشر أي نشرة دورية جديدة حتى الآن. بمجرد رفع نشرة جديدة من قبل الإدارة ستظهر هنا فوراً.",
        "bulletin_upload_title": "رفع نشرة جديدة",
        "bulletin_upload_desc": "نشر تعميم أو نشرة تمريضية بصيغة PDF لجميع منسوبي التجمع الصحي. ستظهر النشرة مباشرة لجميع المستخدمين.",
        "bulletin_drag_text": "اسحب وأفلت ملف الـ PDF هنا، أو انقر للاختيار",
        "bulletin_drag_sub": "ملفات PDF فقط (الحد الأقصى 15 ميجابايت)",
        "bulletin_upload_btn": "رفع النشرة",
        "bulletin_download_btn": "فتح وتحميل النشرة (PDF)",
        "bulletin_delete_btn": "حذف النشرة الحالية",
        
        // Approvals Page
        "approvals_title": "طلبات اعتماد الحسابات",
        "approvals_subtitle": "مراجعة واعتماد طلبات تسجيل منسقي المستشفيات الجدد.",
        "tab_pending": "قيد المراجعة",
        "tab_approved": "المعتمدة",
        "tab_rejected": "المرفوضة",
        "tab_all": "الكل",
        "approval_status_pending": "قيد المراجعة",
        "approval_status_approved": "معتمد ومقبول",
        "approval_status_rejected": "مرفوض",
        "empty_pending_accounts": "لا توجد حسابات معلقة حالياً. تم اعتماد جميع الطلبات!",
        
        // Manage Attendees Page
        "manage_title": "إدارة حضور المسجلين",
        "manage_subtitle": "مراجعة قوائم المتقدمين، اعتماد أو رفض الطلبات، وتصدير الكشوفات.",
        "tab_hosted_attendees": "المسجلين في الأنشطة المستضافة",
        "tab_reg_requests": "طلبات التسجيل",
        "th_name": "اسم المسجل",
        "th_contact": "معلومات الاتصال",
        "th_registered_at": "تاريخ التسجيل",
        "th_hospital": "المستشفى",
        "th_email": "البريد الإلكتروني",
        "th_phone": "الجوال",
        "th_status": "الحالة",
        "th_actions": "الإجراءات",
        "th_participant": "المشارك",
        "th_requested_activity": "النشاط المطلوب",
        "th_activity_date": "تاريخ النشاط",
        "th_approvals_status": "حالة الاعتماد",
        "btn_approve": "قبول واعتماد",
        "btn_reject": "رفض",
        "btn_export": "تصدير إلى Excel",
        "empty_reg_requests": "تمت مراجعة جميع الطلبات! لا توجد طلبات تسجيل معلقة.",
        
        // Profile Page
        "profile_title": "الملف الشخصي",
        "profile_subtitle": "بيانات الكادر التمريضي المسجلة والمستشفى التابع له.",
        "lbl_full_name": "الاسم الكامل",
        "lbl_email": "البريد الإلكتروني",
        "lbl_phone": "رقم الهاتف",
        "lbl_hospital": "المستشفى المعين",
        "lbl_role": "الدور / الصلاحية",
        "btn_back_home": "العودة للوحة التحكم",
        "btn_edit_profile": "تعديل الملف",
        "btn_change_pw": "تغيير كلمة المرور",
        "btn_back": "العودة",
        "profile_note": "تتم إدارة وتحديث هذه البيانات رسمياً من قبل إدارة التمريض بالتجمع الصحي.",
        "role_coordinator": "منسق مستشفى",
        "role_admin": "مدير النظام",
        "role_manager": "المدير العام",
        
        // Login & Signup Pages
        "login_title": "تسجيل الدخول",
        "login_subtitle": "خاص بمنسقي المستشفيات والمسؤولين",
        "login_email": "البريد الإلكتروني",
        "login_password": "كلمة المرور",
        "login_remember": "تذكرني",
        "login_forgot": "نسيت كلمة المرور؟",
        "login_btn": "تسجيل الدخول",
        "login_coordinator_btn": "تسجيل منسق جديد",
        "login_no_account": "ليس لديك حساب منسق؟",
        "login_register_here": "سجل هنا",
        "login_create_one": "أنشئ حساباً جديداً هنا",
        "login_back_home": "العودة للرئيسية",
        "brand_login_desc": "ربط الكوادر التمريضية بفرص التعليم والتطوير المهني المستمر في تجمع الأحساء الصحي.",
        
        "signup_title": "تسجيل منسق جديد",
        "signup_subtitle": "إنشاء حساب منسق مستشفى في منصة NurSync",
        "signup_name": "الاسم الثلاثي",
        "signup_name_ph": "مثال: سارة أحمد",
        "signup_email": "البريد الإلكتروني (العمل/الشخصي)",
        "signup_email_ph": "name@example.com",
        "signup_phone": "رقم الجوال",
        "signup_phone_ph": "05xxxxxxxx",
        "signup_hospital": "المستشفى التابع له",
        "signup_hospital_default": "-- اختر المستشفى أو القطاع --",
        "signup_password": "كلمة المرور",
        "signup_password_ph": "8 خانات على الأقل، أحرف وأرقام",
        "signup_confirm_pw": "تأكيد كلمة المرور",
        "signup_confirm_pw_ph": "أعد إدخال كلمة المرور",
        "signup_pw_rules": "يجب ألا تقل كلمة المرور عن 8 خانات وتحتوي على أحرف وأرقام.",
        "signup_btn": "إنشاء الحساب",
        "signup_has_account": "لديك حساب بالفعل؟",
        "signup_have_account": "لديك حساب بالفعل؟",
        "signup_login_link": "سجل دخولك هنا",
        "signup_login_here": "سجل دخولك هنا",
        "signup_back_home": "العودة للرئيسية",
        
        // Password Rules Box
        "pw_rule_title": "يجب أن تحتوي كلمة المرور على:",
        "pw_rule_length": "8 خانات على الأقل",
        "pw_rule_letter": "حرف واحد على الأقل (a-z, A-Z)",
        "pw_rule_number": "رقم واحد على الأقل (0-9)",
        
        // Hero & Sidecards
        "brand_login_title": "التميز التمريضي",
        "brand_signup_title": "انضم لمنسقي التجمع",
        "brand_signup_desc": "أنشئ حسابك كمنسق لإدارة الأنشطة والبرامج التدريبية بكفاءة عبر تجمع الأحساء الصحي.",
        
        // Footer
        "footer_rights": "جميع الحقوق محفوظة - إدارة التمريض بتجمع الأحساء الصحي",
        "footer_developed": "صُمم وطُوّر لخدمة التميز في الرعاية الصحية",

        // Profile & Approvals Workflow
        "btn_edit_profile": "تعديل البيانات",
        "btn_change_pw": "تغيير كلمة المرور",
        "btn_back": "رجوع",
        "lbl_full_name": "الاسم الكامل",
        "lbl_email": "البريد الإلكتروني",
        "lbl_phone": "رقم الجوال",
        "lbl_hospital": "المستشفى / القطاع التابع له",
        "pw_current": "كلمة المرور الحالية",
        "pw_new": "كلمة المرور الجديدة",
        "pw_confirm": "تأكيد كلمة المرور الجديدة",
        "btn_update_pw": "تحديث كلمة المرور",
        "profile_note": "تتم إدارة واعتماد هذه البيانات من قِبل إدارة التجمع الصحي.",
        "profile_pending_alert": "لديك طلب تعديل بيانات قيد المراجعة والاعتماد حالياً من قِبل إدارة التجمع الصحي (AHC).",
        "profile_pending_badge": "تعديل قيد الاعتماد",
        "badge_new_account": "طلب حساب جديد",
        "badge_profile_update": "طلب تعديل بيانات منسق",
        "lbl_requested_changes": "التعديلات المطلوبة",
        "lbl_current_data": "الحالي",
        "lbl_new_data": "المطلوب",
        "toast_profile_submitted": "تم إرسال طلب تعديل البيانات بنجاح، وهو بانتظار اعتماد التجمع.",
        "toast_profile_approved": "تم اعتماد تعديل البيانات بنجاح.",
        "toast_profile_rejected": "تم رفض طلب تعديل البيانات."
    }
};

/**
 * Get the current active language ('en' or 'ar')
 */
function getCurrentLang() {
    return localStorage.getItem('nursync_lang') || 'en';
}

/**
 * Get translated text for a specific key
 */
function t(key) {
    const lang = getCurrentLang();
    return (translations[lang] && translations[lang][key]) || (translations['en'][key]) || key;
}

/**
 * Apply translation to all DOM elements with data-i18n attributes
 */
function applyLanguage(lang) {
    if (!lang || (lang !== 'en' && lang !== 'ar')) {
        lang = 'en';
    }
    
    // Save to localStorage
    localStorage.setItem('nursync_lang', lang);
    
    // Update HTML attributes
    document.documentElement.lang = lang;
    document.documentElement.dir = (lang === 'ar') ? 'rtl' : 'ltr';
    
    // Update all elements with data-i18n
    const elements = document.querySelectorAll('[data-i18n]');
    elements.forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (translations[lang] && translations[lang][key]) {
            // If the element has icon child, preserve it
            const icon = el.querySelector('i');
            if (icon) {
                const iconClone = icon.cloneNode(true);
                el.textContent = ' ' + translations[lang][key];
                el.insertBefore(iconClone, el.firstChild);
            } else {
                el.textContent = translations[lang][key];
            }
        }
    });
    
    // Update placeholders
    const placeholderElements = document.querySelectorAll('[data-i18n-ph]');
    placeholderElements.forEach(el => {
        const key = el.getAttribute('data-i18n-ph');
        if (translations[lang] && translations[lang][key]) {
            el.placeholder = translations[lang][key];
        }
    });
    
    // Update titles / tooltips
    const titleElements = document.querySelectorAll('[data-i18n-title]');
    titleElements.forEach(el => {
        const key = el.getAttribute('data-i18n-title');
        if (translations[lang] && translations[lang][key]) {
            el.title = translations[lang][key];
        }
    });

    // Update all language toggle buttons text on the page
    const langBtnTexts = document.querySelectorAll('.lang-btn-text');
    langBtnTexts.forEach(btnSpan => {
        btnSpan.textContent = translations[lang]['lang_btn_text'];
    });
    
    // Dispatch custom event in case specific pages need to re-render dynamic items
    window.dispatchEvent(new CustomEvent('languageChanged', { detail: { lang: lang } }));
}

/**
 * Toggle language between English and Arabic
 */
function toggleLanguage() {
    const current = getCurrentLang();
    const nextLang = (current === 'ar') ? 'en' : 'ar';
    applyLanguage(nextLang);
}

// Auto-run on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    applyLanguage(getCurrentLang());
});
