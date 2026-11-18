<?php /** @var array $_ */ ?>
<section id="upload-rules-admin" class="section" dir="rtl" lang="fa"
 data-save-url="<?php p($_['saveUrl']); ?>" data-directory-url="<?php p($_['directoryUrl']); ?>" data-policy="<?php p(json_encode($_['policy'], JSON_UNESCAPED_UNICODE)); ?>">
 <div class="ur-heading"><div><h2>قوانین آپلود</h2><p>برای هر نوع فایل، افراد مشمول و پیام دلخواه تعیین کنید.</p></div><button id="ur-add" type="button" class="primary">＋ افزودن قانون جدید</button></div>
 <label class="ur-master"><input id="ur-enabled" type="checkbox"> اجرای قوانین آپلود فعال باشد</label>
 <div class="ur-guide">قوانین از بالا به پایین بررسی می‌شوند؛ اولین قانون فعال که با فایل و آپلودکننده تطبیق داشته باشد اجرا می‌شود. فرد مستثنا از همان قانون عبور می‌کند و ممکن است قانون بعدی شامل او شود. اگر هیچ قانونی منطبق نباشد، این افزونه محدودیتی اعمال نمی‌کند.</div>
 <p id="ur-status" role="status" aria-live="polite"></p>
 <div id="ur-list"></div><p id="ur-empty" hidden>هنوز قانونی ندارید. با «افزودن قانون جدید» شروع کنید.</p>
 <p class="ur-note">«همه» شامل کاربران و آپلود مهمان است. در حالت انتخابی، عضویت در یکی از گروه‌ها یا حضور در فهرست کاربران کافی است. ملاک، شخص آپلودکننده است. انتخاب پسوند بر اساس نام فایل است.</p>
 <p class="ur-note">فایل‌های موجود تغییری نمی‌کنند. محدودیت سهمیه و وب‌سرور مستقل است. نسخهٔ افزونه: ۳.۰.۰ — Nextcloud 34.</p>
 <dialog id="ur-dialog" aria-labelledby="ur-dialog-title" dir="rtl">
  <form id="ur-editor">
   <div class="ur-heading"><h2 id="ur-dialog-title">افزودن قانون جدید</h2><button id="ur-close" type="button" aria-label="بستن">×</button></div>
   <label class="ur-field">نام قانون<input id="ur-name" required maxlength="100" placeholder="مثلاً ویدئوهای گروه آموزش"></label>
   <div class="ur-grid"><label class="ur-field">پسوندها<input id="ur-extensions" required dir="ltr" placeholder="mp4, mkv"><small>با ویرگول جدا کنید؛ برای همهٔ فایل‌ها * بنویسید.</small></label><label class="ur-field">نوع محدودیت<select id="ur-mode"><option value="limit">حداکثر حجم فایل</option><option value="block">آپلود این پسوندها ممنوع</option></select></label></div>
   <label class="ur-field">حجم مجاز هر فایل (مگابایت)<input id="ur-size" type="number" min="0.000001" max="1048576" step="any" required dir="ltr"><small>هر مگابایت ۱۰۲۴ × ۱۰۲۴ بایت است؛ فایل دقیقاً برابر سقف مجاز پذیرفته می‌شود.</small></label>
   <label class="ur-field">پیام نمایش‌داده‌شده به کاربر<textarea id="ur-message" required maxlength="500" rows="2"></textarea><small>متغیرهای اختیاری: {filename} نام فایل، {limit} حجم مجاز، {extension} پسوند.</small></label>
   <label class="ur-field">این قانون برای چه کسانی اجرا شود؟<select id="ur-audience"><option value="all">همهٔ کاربران و مهمان‌ها</option><option value="selected">گروه‌ها و کاربران انتخاب‌شده</option></select></label>
   <div id="ur-targets" class="ur-grid"><div id="ur-groups"></div><div id="ur-users"></div></div>
   <div id="ur-excluded"></div>
   <p class="ur-note">استثنا بر انتخاب مستقیم و عضویت در گروه اولویت دارد، اما فقط برای همین قانون.</p>
   <label class="ur-master"><input id="ur-rule-enabled" type="checkbox"> این قانون فعال باشد</label>
   <p id="ur-editor-status" role="alert"></p>
   <div class="ur-dialog-actions"><button id="ur-commit" type="submit" class="primary">ذخیره قانون</button><button id="ur-cancel" type="button">انصراف</button></div>
  </form>
 </dialog>
</section>
