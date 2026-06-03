# تقرير المرحلة 0 — تحرٍّ وتشخيص (دون تعديل كود)

> الحالة: تحرٍّ فقط. لم يُعدَّل أي ملف مصدري. هذا التقرير للمراجعة قبل الانتقال لمرحلة الإصلاح.
> التاريخ: 2026-06-04 · الفرع: `main` · آخر commit: `c9b1154` (2026-02-20)

---

## ملخّص تنفيذي (الأهم أولاً)

| # | الموضوع | الخلاصة |
|---|---------|---------|
| 1 | البناء | ✅ ينجح (exit 0). تحذير واحد فقط غير قاتل: استيراد `UserVisually` غير موجود في `lucide-react`. |
| 2 | `/admin/users` | ✅ موجود كـ route فعلي ومربوط في القائمة بشكل صحيح. |
| 3 | `/system-health` | ❌ **ليس route فعليًّا**. المكوّن `SystemHealth` مستورد لكن غير مربوط بأي `<Route>`. رابط القائمة يؤدي لـ catch-all → `/dashboard`. |
| 4 | تعدّد الدول (multi-country) | ⚠️ **مُنفّذ بالفعل** في الواجهة والـ backend. البند المطلوب في المرحلة 1/2 شبه مكتمل. (تفاصيل أدناه) |
| 5 | `Authorization` على مسارات الدخول | ✅ العيب مؤكَّد: `getHeaders()` يضيف `Bearer` لكل الطلبات بما فيها `/aqop-jwt/v1/*`. |
| 6 | سباق أول دخول | ⚠️ في **هذا الريبو** المسار يبدو سليمًا (انتقال أمري بعد ضبط الحالة). العيب الموصوف للإنتاج قد يكون من نسخة مايو المفقودة. التصليب مطلوب احتياطيًّا. |
| 7 | قاعدة الـ API في `.env.production` | ❌ خاطئة: `operation.aqleeat.co/wp-json` بدل نفس الأصل `leads.aqleeat.com` بدون `/wp-json`. |
| 8 | مخرجات `dist` | ✅ نظيفة — لا سكربتات حقن في `index.html` الناتج (ولا في أرشيفات الـ dist المرفقة). |

---

## بند 1 — نتائج البناء

**ملاحظة بيئية:** بيئة العمل لا تحتوي `npm`/`node` في الـ PATH (يوجد `node v24` فقط ضمن codex-runtime بلا `npm`). لتشغيل البناء فعليًّا، جرى تنزيل `npm@11.16.0` المستقل من السجل (registry) وتشغيله عبر `node`. **هذا الإجراء بيئي بحت ولم يمسّ أي كود في الريبو.**

```
$ npm ci      → exit 0 (تحذير فقط: install scripts لـ esbuild/fsevents لم تُنفَّذ، ولم تؤثّر على البناء)
$ npm run build → exit 0 — built in ~2s
```

**التحذير الوحيد (غير قاتل):**
```
src/pages/Admin/UserManagement.jsx (19:2): "UserVisually" is not exported by
"node_modules/lucide-react/.../lucide-react.js", imported by ".../UserManagement.jsx".
```
- `UserVisually` مستورد في السطر 19 لكنه **غير مستخدَم** في المكوّن (الأيقونات المستخدَمة فعليًّا: `User`, `Plus`, `Edit2`...).
- Rollup يحوّل الاستيراد غير المحلول إلى `undefined` ويُكمل البناء (تحذير لا خطأ). لو استُخدم مستقبلًا لانهار وقت التشغيل. **يجب تنظيفه** (الأرجح أنه كان `User` فتشوّه).
- ناتج البناء سليم؛ أكبر chunk هو `ui-vendor` (recharts) ~431KB، وهو ضمن حدود التحذير المضبوطة على 1000KB.

---

## بند 2 — جدول الراوتات الفعلي (من `src/main.jsx`)

### عامّة (بلا مصادقة)
| المسار | المكوّن |
|--------|---------|
| `/login` | LoginPage |
| `/submit-lead` | LeadForm (نموذج عام) |

### محميّة — بلا قيد دور
| المسار | المكوّن |
|--------|---------|
| `/dashboard` | DashboardPage |
| `/notifications` | NotificationsPage |
| `/settings/notifications` | NotificationSettingsPage |
| `/notification-settings` | NotificationSettingsPage *(مكرّر لنفس المكوّن أعلاه)* |
| `/follow-ups` | FollowUpsPage |
| `/profile` | ProfilePage |

### محميّة — مقيّدة بدور (`requiredRole`، هرمية)
| المسار | الدور الأدنى | المكوّن |
|--------|--------------|---------|
| `/leads` | AGENT | MyLeads |
| `/add-lead` | AGENT | AddLead |
| `/leads/:id` | AGENT | LeadDetail |
| `/settings/faq` | AGENT | FAQSettings |
| `/supervisor/team-leads` | SUPERVISOR | TeamLeads |
| `/manager/all-leads` | COUNTRY_MANAGER | AllLeads |
| `/manager/analytics` | COUNTRY_MANAGER | Analytics |
| `/manager/bulk-whatsapp` | COUNTRY_MANAGER | BulkWhatsAppJobs |
| `/manager/reports` | COUNTRY_MANAGER | ReportsDashboard |
| `/settings/conversion-targets` | COUNTRY_MANAGER | ConversionTargetsSettings |
| `/manager/automation` | OPERATION_MANAGER | Automation |
| `/settings/learning-paths` | OPERATION_ADMIN | LearningPathSettings |
| **`/admin/users`** | **OPERATION_ADMIN** | **UserManagement** ✅ |
| `/settings/facebook-leads` | DIGITAL_MARKETING | FacebookLeadsSettings |

### افتراضي
| المسار | السلوك |
|--------|--------|
| `/` | إعادة توجيه → `/dashboard` |
| `*` (404) | إعادة توجيه → `/dashboard` |

### تأكيدات المطلوبة
- **`/admin/users`**: ✅ موجود ومربوط في `Navigation.jsx:128` بشكل صحيح (لا حاجة لتوجيه `/users` المحقون في الإنتاج).
- **`/system-health`**: ❌ **غير موجود كـ route**. تفاصيل:
  - `main.jsx:29` يستورد `SystemHealth` (lazy) لكن **لا يوجد `<Route path="/system-health">`** في الملف.
  - `Navigation.jsx:132` يضع رابطًا `<NavItem to="/system-health" ...>` للمسؤولين → ينتهي بالـ catch-all `*` → `/dashboard`. أي أن زر "System Health" مكسور.
  - الإصلاح بسيط: إضافة route للمكوّن الموجود أصلًا.
- **`/manager/*`**: ✅ خمسة مسارات (all-leads, analytics, automation, bulk-whatsapp, reports).
- **`/settings/*`**: ✅ خمسة مسارات (notifications, facebook-leads, learning-paths, faq, conversion-targets).

**ملاحظة ثانوية:** تقارير فرعية موجودة كملفات (`Reports/AgentPerformanceReport.jsx`, `CampaignsReport.jsx`, `CountryReport.jsx`, `SourcesReport.jsx`, `StatusReport.jsx`, `TimeAnalysisReport.jsx`) لكن المربوط هو `ReportsDashboard` فقط؛ يُفترض أنها تُعرض داخليًّا بالتبويب لا كمسارات منفصلة. (لا يلزم إجراء.)

---

## بند 3 — حقل الدولة في `UserManagement.jsx` والـ backend

### الواجهة (الحالة الفعلية)
خلافًا للافتراض في التكليف، **الحقل بالفعل multi-select**، وليس single-select:

- حالة الفورم: `country_ids: []` (مصفوفة) + `can_see_unassigned_countries: false` — سطور 50‑58.
- واجهة الاختيار: **قائمة checkboxes** على الدول (سطور 624‑645)، مع شرائح (chips) للمحدَّد وزر حذف لكل دولة (647‑665).
- **الإضافة** (`handleSubmit`, create): يرسل `country_ids: formData.country_ids.map(id => parseInt(id))` — سطر 216.
- **التعديل** (update): يرسل `country_ids: [...]` كمصفوفة + `can_see_unassigned_countries` — سطور 227‑228.
- **تحميل القيم عند التعديل**: `country_ids: user.country_ids || (user.country_id ? [user.country_id] : [])` — سطر 150 (يتعامل مع التوافق الخلفي).
- العرض في الجدول: يعرض `country_names[]` كشرائح، مع fallback لـ `country_name` المفرد (458‑473).
- الـ endpoint: `updateUser` → `PUT /aqop/v1/users/{id}` و `createUser` → `POST /aqop/v1/users` (من `api/users.js`).
- مصدر الدول: `getCountries()` → `apiClient.get('/aqop/v1/leads/countries')`، ويُقرأ `country.id` و `country.country_name_ar||country_name_en`.

### الـ backend (`wp-content/plugins/aqop-leads/api/class-users-api.php`)
**يقبل المصفوفة بالكامل** (الكلاس الفعّال هو `AQOP_Leads_Users_API`):

- **التحديث** (سطور 380‑391): `if (isset($params['country_ids']))` → `array_map('absint', ...)` → `update_user_meta($user_id, 'aq_assigned_countries', $clean_ids)`؛ مع fallback لـ `country_id` المفرد (391‑399).
- **الإنشاء** (سطور 278‑288): نفس المنطق.
- **القراءة** (155‑171، 203‑221): يُرجع `country_ids` (مصفوفة)، `country_names`، `country_id` (للتوافق الخلفي)، و `can_see_unassigned_countries`.
- مفتاح الميتا: `aq_assigned_countries` (جمع).

### الخلاصة المهمّة
**ميزة تعدّد الدول مُنفّذة فعليًّا في الواجهة والـ backend معًا** → بند المرحلة 1/2 شبه مكتمل. ما يتبقّى تحقّق منه فقط (وليس تنفيذًا):
1. تنظيف استيراد `UserVisually` المكسور (بند 1).
2. التأكد من تطابق `country.id` القادم من `/leads/countries` مع المعرّفات التي يخزّنها الـ backend (تحقّق ببيانات حيّة على مسار الاختبار).
3. ⚠️ **خطر كامن (backend، خارج هذا الريبو):** يوجد كلاس قديم single-country `AQOP_Users_API` في `wp-content/plugins/aqop-core/api/class-users-api.php` يسجّل نفس المسار `aqop/v1/users` لكنه **معطّل بالتعليق** في `aqop-core.php:54‑55`. لو أُعيد تفعيله مستقبلًا فسيتعارض مع نسخة `aqop-leads` ويكسر تعدّد الدول (يستخدم مفتاح ميتا مختلفًا `aq_assigned_country` مفرد). لا إجراء الآن، لكن وجب التنبيه.

---

## بند 4 — تحليل المصادقة

### 4.أ — `Authorization` يُرسَل خطأً على مسارات الدخول ✅ (مؤكَّد)
- `api/index.js` → `getHeaders()` (سطور 22‑33) يضيف `Authorization: Bearer <token>` **لو وُجد توكن في localStorage، لكل الطلبات** التي تمرّ عبر `request()`.
- `login`/`logout`/`validate` في `api/auth.js` تمرّ عبر `apiClient.post(...)` → `request()` → `getHeaders()` → ستحمل التوكن إن وُجد.
  - أول دخول (لا توكن مخزّن): لا يُرسَل header → غير ضار صدفةً.
  - إعادة دخول بوجود توكن قديم/منتهٍ مخزّن: يُرسَل `Bearer` بايت → قد يربك الـ backend أو يُعالَج كجلسة منتهية.
- **تناقض داخلي يستحق التوحيد:** تجديد التوكن له مساران:
  - `api/index.js → refreshAccessToken()` يستخدم `fetch` خاصًّا **بدون** `Authorization` (✅ صحيح).
  - `api/auth.js → refreshToken()` يمرّ عبر `apiClient.post` → **يضيف** `Authorization` (✗).
- **الإصلاح المقترح (مرحلة 1):** في `getHeaders()` أو `request()` استثناء مسارات `/aqop-jwt/v1/*` (login/refresh/logout/validate) من إضافة `Authorization`.

### 4.ب — سباق أول دخول ⚠️ (تحليل صريح)
سلوك الريبو الحالي يبدو **سليمًا** ولا يُعيد إنتاج العيب بوضوح:
- `LoginPage.handleSubmit` (سطور 20‑38): `await login()` ثم `if (result.success) navigate('/dashboard')` — انتقال **أمري** بعد نجاح الدخول.
- `AuthContext.login` (40‑55): يضبط `setUser(...)` و `setIsAuth(true)` **بشكل متزامن** قبل العودة، ثم يحدث الانتقال. React 19 يجمّع التحديثات (batching)، فيُعاد العرض و `isAuth=true`.
- `ProtectedRoute` (11‑29): `loading` يصبح `false` بعد `checkAuth` عند الإقلاع؛ ثم يعتمد على `isAuth`.

> **استنتاج صريح:** العيب الموصوف («أول محاولة تنجح لكن لا تنتقل») غالبًا من **نسخة الإنتاج المفقودة (مايو)** التي قد تكون اعتمدت على `useEffect([isAuth])` للانتقال (نمط معرّض للسباق)، لا من كود الريبو الحالي. لم أتمكّن من إعادة إنتاج السباق ساكنًا في هذا الريبو.

**رغم ذلك، التصليب الاحتياطي المطلوب مفيد ووجيه:** `ProtectedRoute` يعتمد **فقط** على حالة React `isAuth` (المشتقّة)، لا على وجود التوكن. لو حدث أي اختلال ترتيب (إعادة تركيب `AuthProvider`، انتقال قبل commit الحالة) سيُرتدّ المستخدم لـ `/login` رغم وجود توكن صالح. الإصلاح: جعل `ProtectedRoute` يقبل `isAuth || isAuthenticated()` (وجود التوكن) كمصدر حقيقة احتياطي — كما طُلب في المرحلة 1.

### 4.ج — ملاحظات ثانوية
- `constants.js:5` يعرّف `API_URL` افتراضي `http://localhost:8888/...` بينما `api/index.js:7` افتراضي `https://operation.aqleeat.co/wp-json`. القيمة الفعّالة للطلبات من `api/index.js`؛ تعريف `constants.API_URL` يبدو غير مستخدَم — تناقض تجميلي يُفضّل توحيده.
- `handleLogout` يستخدم `window.location.href = '/login'` (إعادة تحميل كاملة) — مقبول.

---

## بند 5 — قاعدة الـ API والبناء للإنتاج

- `aqop-frontend/.env.production` الحالي:
  ```
  VITE_API_URL=https://operation.aqleeat.co/wp-json
  ```
- بحسب التشخيص المؤكّد، الإنتاج يستدعي **نفس الأصل** `leads.aqleeat.com` بمسارات `/aqop/v1` و `/aqop-jwt/v1` **بدون** `/wp-json`.
- إذًا القيمة خاطئة على محورين: (1) المضيف، (2) لاحقة `/wp-json`. مع القيمة الحالية ستصبح الطلبات `operation.aqleeat.co/wp-json/aqop/v1/...` بدل `leads.aqleeat.com/aqop/v1/...`.
- **الإصلاح المقترح (مرحلة 1، بانتظار تأكيدك للمضيف):**
  ```
  VITE_API_URL=https://leads.aqleeat.com
  ```
  (نفس الأصل، بلا `/wp-json`؛ والـ endpoints في الكود تبدأ بـ `/aqop/...` و `/aqop-jwt/...`). يلزم تأكيد أن الـ REST يُخدَم عند الجذر بلا `/wp-json` على بيئة الاختبار قبل التثبيت.

---

## بند 6 — مخرجات `dist` ونظافة الحقن

- `index.html` المصدري (`aqop-frontend/index.html`) نظيف ويحمّل `/src/main.jsx` فقط.
- `dist/index.html` بعد البناء **نظيف**: لا سكربتات حقن، فقط `index-*.js` + modulepreload + css.
- الأرشيفات المرفقة في الريبو (`aqop-frontend-dist.tar.gz`, `frontend-dist.tar.gz`, `frontend-final.tar.gz`) فُحص `index.html` داخلها → **جميعها نظيفة** بلا حقن.
- الاستنتاج: التوجيه والقائمة يعملان أصليًّا عبر React Router بروابط للمسارات الحقيقية؛ سكربتات الحقن في الإنتاج (توجيه `/users`، إصلاح الدخول) **لا لزوم لها في بناء نظيف** متى صُلِّحت العيوب أدناه في الكود.

---

## خطة الإصلاح المقترحة (المرحلة 1 — كل بند = commit مستقل)

| # | الإصلاح | الملفات | المخاطرة |
|---|---------|---------|----------|
| 1 | استثناء `/aqop-jwt/v1/*` من إضافة `Authorization`؛ توحيد مسار التجديد | `api/index.js` (+ ربما `api/auth.js`) | منخفضة |
| 2 | تصليب `ProtectedRoute` ليقبل وجود التوكن (`isAuthenticated()`) كمصدر احتياطي، وضمان انتقال أول دخول | `auth/ProtectedRoute.jsx`, `auth/AuthContext.jsx` | منخفضة |
| 3 | تنظيف استيراد `UserVisually` المكسور | `pages/Admin/UserManagement.jsx` | لا تُذكر |
| 4 | إضافة route لـ `/system-health` (المكوّن موجود) | `main.jsx` | منخفضة |
| 5 | ضبط `VITE_API_URL` للإنتاج (بعد تأكيد المضيف) + توثيق خطوات البناء | `.env.production`, `SETUP.md` | منخفضة (يلزم تأكيد) |
| 6 | (اختياري) توحيد `constants.API_URL` مع `api/index.js` | `utils/constants.js` | لا تُذكر |
| — | تعدّد الدول: **منفّذ مسبقًا** — لا حاجة لتحويل single→multi. يكفي التحقّق ببيانات حيّة. | — | — |

---

## قائمة الفجوات / المخاطر (الريبو مقابل الإنتاج، فبراير→مايو)

1. **`/system-health` بلا route** — قد يكون أُضيف في بناء مايو؛ هنا الزر مكسور. (إصلاح بسيط متاح.)
2. **`.env.production` خاطئ** (المضيف + `/wp-json`) — يجب ألّا يُبنى للإنتاج بهذه القيمة.
3. **استيراد `UserVisually` مكسور** — لا يكسر البناء حاليًّا لكنه دَيْن تقني.
4. **تكرار مسار `aqop/v1/users`** بين `aqop-core` (معطّل) و`aqop-leads` (فعّال) — خطر كامن لو أُعيد تفعيل القديم. (backend خارج الريبو.)
5. **عيب الدخول الموصوف للإنتاج لم يُعَد إنتاجه ساكنًا في الريبو** — مؤشّر قوي على أن نسخة مايو تختلف عن `main`. لا يمكن جزم ما نقص من ميزات فبراير→مايو دون مصدر بناء مايو.
6. **عدم القدرة على إنتاج قائمة ميزات ناقصة قاطعة:** لا تتوفّر شيفرة المصدر لبناء الإنتاج (مايو) للمقارنة المباشرة؛ كل ما سبق مبني على فحص الريبو + التشخيص المؤكّد المُعطى. **لن أخمّن ميزات؛ أحتاج توجيهك** إن وُجدت سلوكيات إنتاج محدّدة تريد التأكد من وجودها في الريبو (مثلًا: شاشات/أزرار/تقارير بعينها) لأقارنها بنديًّا.

---

## ما لم أفعله (التزامًا بحدود المرحلة 0)
- لم أُعدّل أي ملف مصدري في `src/` ولا إعدادات البناء.
- لم ألمس السيرفر ولا الـ backend الحيّ.
- البناء جرى محليًّا فقط للتحقّق؛ مجلد `dist` الناتج غير متعقَّب في git (مُدرج في `.gitignore`).

**بانتظار موافقتك للانتقال للمرحلة 1.** عند الموافقة سأنفّذ الإصلاحات بـ commits صغيرة موصوفة، وأنبّهك قبل أي تغيير يلزم في الـ backend.
