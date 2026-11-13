حزمة مخطط الرحلات بالذكاء الاصطناعي - Al-Ahsa Vision Hub

الملفات:
- itinerary.php: صفحة مقدمة تخطيط الرحلة.
- survey.php: الاستبيان الكامل.
- generate-itinerary.php: يقرأ الأماكن المنشورة، يرسل تفضيلات السائح إلى OpenAI API، يتحقق من IDs ثم يحفظ الرحلة.
- my-itinerary.php: يعرض الرحلة المحفوظة يومًا بيوم.
- trip-common.php: الهيدر/السيدبار المشترك بنفس هيكل الموقع.
- config/ai-config.php: إعداد مفتاح API.
- css/trip-planner.css: تنسيقات خاصة بصفحات تخطيط الرحلات فقط.

التركيب:
1) خذي نسخة احتياطية من مجلد المشروع.
2) انسخي الملفات والمجلدات إلى مجلد مشروعك في htdocs مع دمج المجلدات.
3) تأكدي أن db.php موجود في جذر المشروع وأنه يتصل بقاعدة al_ahsa_vision_hup_sql.
4) افتحي config/ai-config.php وضعي مفتاح OpenAI API مكان PASTE_YOUR_OPENAI_API_KEY_HERE.
5) تأكدي أن PHP cURL مفعّل في XAMPP: افتحي php.ini، أزيلي ; قبل extension=curl، ثم أعيدي تشغيل Apache.
6) اجعلي زر Plan Your Trip في index.php يفتح itinerary.php.
7) سجّلي دخولك كسائح، ثم افتحي itinerary.php وابدئي الاستبيان.

ملاحظات مهمة:
- يعتمد النظام على جدول Places الحالي، ويأخذ فقط السجلات التي Is_Published = 1.
- الذكاء الاصطناعي يختار Place_ID من القائمة التي تأتي من قاعدة البيانات فقط، ثم يتحقق PHP من كل ID قبل الحفظ.
- الحفظ يستخدم أسماء الأعمدة التي زودتِنا بها:
  survey(tourist_id, create_at)
  itinerary(itinerary_id, tourist_id, survey_id, days, create_at)
  itinerary_items(item_id, itinerary_id, day_number, place_id, start_time, end_time, sequence_number, reason_chosen)
- لا يكتب هذا الإصدار في survey_answers لأننا لا نملك قائمة Question_ID/Option_ID الفعلية المعتمدة للاستبيان؛ إجابات الاستبيان تُرسل مباشرة إلى نموذج الذكاء الاصطناعي. لا يحذف أو يغير أي جدول موجود.
- تأكدي أن نوع start_time و end_time يقبل الوقت (TIME).
- أسماء الملفات والجداول في PHP قد تتأثر بإعدادات حساسية الأحرف في الاستضافة؛ اختبريها محليًا أولًا.
- لا ترفعي مفتاح API الحقيقي إلى GitHub أو تشاركيه.

ملاحظة ربط زر الهوم:
<a href="itinerary.php">Plan Your Trip</a>

يعتمد الاتصال على OpenAI Responses API، ويمكن تغيير اسم النموذج من OPENAI_MODEL في config/ai-config.php.
