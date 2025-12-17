<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ترجمات النماذج
    |--------------------------------------------------------------------------
    */

    // أنواع الحقول
    'text' => 'نص',
    'textarea' => 'منطقة نص',
    'number' => 'رقم',
    'email' => 'بريد إلكتروني',
    'password' => 'كلمة مرور',
    'select' => 'اختيار',
    'multiselect' => 'اختيار متعدد',
    'checkbox' => 'مربع اختيار',
    'radio' => 'زر راديو',
    'toggle' => 'تبديل',
    'switch' => 'مفتاح',
    'date' => 'تاريخ',
    'time' => 'وقت',
    'datetime' => 'تاريخ ووقت',
    'file' => 'ملف',
    'image' => 'صورة',
    'color' => 'لون',
    'url' => 'رابط',
    'tel' => 'هاتف',
    'range' => 'نطاق',
    'hidden' => 'مخفي',
    'rich_text' => 'نص منسق',
    'markdown' => 'ماركداون',
    'code' => 'كود',
    'json' => 'JSON',

    // النصوص التوضيحية الشائعة
    'enter_name' => 'أدخل الاسم',
    'enter_email' => 'أدخل البريد الإلكتروني',
    'enter_password' => 'أدخل كلمة المرور',
    'enter_phone' => 'أدخل رقم الهاتف',
    'enter_url' => 'أدخل الرابط',
    'enter_description' => 'أدخل الوصف',
    'enter_value' => 'أدخل القيمة',
    'enter_amount' => 'أدخل المبلغ',
    'enter_search' => 'بحث...',
    'select_option' => 'اختر خياراً',
    'select_date' => 'اختر التاريخ',
    'select_time' => 'اختر الوقت',
    'select_file' => 'اختر ملف',
    'select_color' => 'اختر لون',
    'choose_file' => 'اختر ملف',
    'no_file_chosen' => 'لم يتم اختيار ملف',
    'browse' => 'تصفح',

    // تسميات الحقول
    'label_name' => 'الاسم',
    'label_email' => 'البريد الإلكتروني',
    'label_password' => 'كلمة المرور',
    'label_confirm_password' => 'تأكيد كلمة المرور',
    'label_phone' => 'الهاتف',
    'label_address' => 'العنوان',
    'label_city' => 'المدينة',
    'label_state' => 'المنطقة/المحافظة',
    'label_country' => 'الدولة',
    'label_zip' => 'الرمز البريدي',
    'label_description' => 'الوصف',
    'label_notes' => 'ملاحظات',
    'label_date' => 'التاريخ',
    'label_time' => 'الوقت',
    'label_amount' => 'المبلغ',
    'label_quantity' => 'الكمية',
    'label_price' => 'السعر',
    'label_status' => 'الحالة',
    'label_type' => 'النوع',
    'label_category' => 'التصنيف',
    'label_tags' => 'الوسوم',
    'label_image' => 'الصورة',
    'label_file' => 'الملف',
    'label_url' => 'الرابط',
    'label_title' => 'العنوان',
    'label_content' => 'المحتوى',
    'label_message' => 'الرسالة',
    'label_subject' => 'الموضوع',

    // مطلوب/اختياري
    'required' => 'مطلوب',
    'optional' => 'اختياري',
    'required_field' => 'هذا الحقل مطلوب',
    'optional_field' => 'هذا الحقل اختياري',
    'required_fields_note' => 'الحقول المعلمة بـ * مطلوبة',

    // نص المساعدة
    'help_password' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
    'help_email' => 'لن نشارك بريدك الإلكتروني أبداً',
    'help_phone' => 'أضف رمز الدولة للأرقام الدولية',
    'help_file_size' => 'الحد الأقصى لحجم الملف: :size',
    'help_file_types' => 'الأنواع المسموحة: :types',
    'help_image_dimensions' => 'الحجم الموصى به: :width × :height بكسل',

    // رسائل التحقق
    'validation_required' => 'هذا الحقل مطلوب',
    'validation_email' => 'يرجى إدخال بريد إلكتروني صالح',
    'validation_min' => 'الحد الأدنى :min حرف مطلوب',
    'validation_max' => 'الحد الأقصى :max حرف مسموح',
    'validation_between' => 'يجب أن يكون بين :min و :max حرف',
    'validation_numeric' => 'يرجى إدخال رقم صالح',
    'validation_integer' => 'يرجى إدخال رقم صحيح',
    'validation_positive' => 'يرجى إدخال رقم موجب',
    'validation_url' => 'يرجى إدخال رابط صالح',
    'validation_date' => 'يرجى إدخال تاريخ صالح',
    'validation_phone' => 'يرجى إدخال رقم هاتف صالح',
    'validation_match' => 'الحقول غير متطابقة',
    'validation_unique' => 'هذه القيمة مستخدمة بالفعل',
    'validation_accepted' => 'يجب أن توافق على الشروط',

    // رفع الملفات
    'upload_drag_drop' => 'اسحب وأفلت الملفات هنا',
    'upload_or_click' => 'أو انقر للتصفح',
    'upload_multiple' => 'يمكنك رفع ملفات متعددة',
    'upload_progress' => 'جاري الرفع... :percent%',
    'upload_complete' => 'اكتمل الرفع',
    'upload_failed' => 'فشل الرفع',
    'upload_remove' => 'إزالة الملف',
    'upload_preview' => 'معاينة',

    // التاريخ/الوقت
    'today' => 'اليوم',
    'clear' => 'مسح',
    'done' => 'تم',
    'from_date' => 'من تاريخ',
    'to_date' => 'إلى تاريخ',
    'start_date' => 'تاريخ البداية',
    'end_date' => 'تاريخ النهاية',
    'start_time' => 'وقت البداية',
    'end_time' => 'وقت النهاية',

    // الاختيار المتعدد
    'select_all' => 'اختيار الكل',
    'deselect_all' => 'إلغاء اختيار الكل',
    'selected_count' => 'تم اختيار :count',
    'no_options' => 'لا توجد خيارات متاحة',
    'search_options' => 'البحث في الخيارات...',
    'create_new' => 'إنشاء جديد',
    'add_new_option' => 'إضافة ":value"',

    // الوسوم
    'add_tag' => 'إضافة وسم',
    'remove_tag' => 'إزالة وسم',
    'type_to_add' => 'اكتب واضغط Enter للإضافة',
    'max_tags' => 'الحد الأقصى :max وسم مسموح',

    // عداد الأحرف
    'characters' => 'أحرف',
    'characters_remaining' => ':count حرف متبقي',
    'characters_over' => ':count حرف فوق الحد',

    // إجراءات النموذج
    'submit' => 'إرسال',
    'reset' => 'إعادة تعيين',
    'clear_form' => 'مسح النموذج',
    'save_draft' => 'حفظ كمسودة',
    'preview' => 'معاينة',

    // حالات النموذج
    'form_saving' => 'جاري الحفظ...',
    'form_saved' => 'تم الحفظ',
    'form_error' => 'يرجى إصلاح الأخطاء أعلاه',
    'unsaved_changes' => 'لديك تغييرات غير محفوظة',

];
