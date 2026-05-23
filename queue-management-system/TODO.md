- [ ] تعديل api/exit_customer.php: عند إخراج العميل من الكاونتر، جلب العميل التالي waiting لنفس service_type وتعيينه مباشرة serving وربطه بنفس counter
- [x] تعديل api/exit_customer.php: عند إخراج العميل، جلب التالي waiting لنفس service_type وتعيينه مباشرة serving وربطه بنفس counter
- [ ] التأكد من عدم كسر منطق get_queue.php وواجهة index/js (refreshQueue/refreshStats)
- [ ] تنفيذ اختبار يدوي: إخراج عميل من أي ديوان والتأكد أن التالي يظهر مباشرة بنفس الديوان


