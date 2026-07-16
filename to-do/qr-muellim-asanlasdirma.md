# QR Müəllim — İş asanlaşdırma to-do

Məqsəd: müəllimin əlində **bu gün kim gəlməlidir**, **8-lik neçədədir**, **maaş üçün nə qaldı** aydın olsun.

Biznes qaydaları:
- 1 maaş vahidi = **8 tamamlanmış dərs**
- Gün qrupları: **1-4** (B.e + C.a), **2-5** (Ç.a + Cümə), **3-6** (Çərşənbə + Şənbə)

**Varsayımlar (tətbiq):**
- Gün qrupu `muellimler_new.telebeler` cədvəlindən (gün adından) törədilir
- 8 dərs eyni müəllim–tələbə cütü üzrə sayılır
- Maaş vahidləri toplanır; panel ay seçiminə görə dövr hesabatı göstərir

---

## Prioritet 1 — Bu günün tələbə siyahısı

- [x] Tələbə/müəllim əlaqəsinə **gün qrupu** (1-4 / 2-5 / 3-6) bağla
- [x] `qr_muellim.php`-də **“Bu gün”** paneli: yalnız həmin günə aid qrupun tələbələri
- [x] Siyahıda statuslar: gözlənilir / skan olundu / bu gün yox
- [x] Yuxarıda qısa sayğac: Gözlənilən · Skan · Qalan

## Prioritet 2 — 8/8 progress

- [x] Hər tələbə üçün cari dövr: **X/8** (skan sayından hesablanan)
- [x] Skan sonrası mesaj: məs. `Elnur — 6/8 · qalan 2 dərs`
- [x] 8/8-də: dövr bağlandı + **+1 maaş vahidi** + yeni dövr `0/8`
- [x] Siyahıda progress bar / faiz göstəricisi

## Prioritet 3 — Maaş paneli

- [x] Müəllim üçün ay/həftə **maaş vahidi hesabatı**
- [x] Cədvəl: tələbə · tamamlanan 8-lik · natamam (X/8)
- [x] Cəmi maaş vahidi (+ istəyə görə AZN = vahid × tarif)
- [x] Admin eyni hesabatı müəllim seçərək görsün

## Prioritet 4 — Həftə görünüşü (qrup üzrə)

- [x] Seçilmiş qrup üçün həftənin 2 gününü yan-yana göstər (məs. 1 və 4)
- [x] Hər gün üçün checkbox/status: skan oldu / gözlənilir
- [x] “Bu həftə 1 dərsə gəlib, 1 qalıb” tipli xülasə

## Prioritet 5 — Xəbərdarlıqlar

- [x] Bu gün skan etməyənlər — siyahıda vurğula
- [x] 7/8 olanlar — “növbəti dərsdə dövr bağlanır”
- [x] Uzun müddət gəlməyənlər — risk siqnalı

## Sonra (istəyə görə)

- [x] Excel / PDF export (ay və ya həftə) — CSV export
- [x] Canlı skan siyahısı (QR açıq ikən real-time) — 8 sn polling
- [x] Çap üçün təmiz QR kart/viza səhifəsi
- [ ] Saxta skan qorunması (təkrar, qısaömürlü token və s.) — eyni gün təkrar artıq bloklanır; token sonra

---

## Açıq suallar (tətbiqdən əvvəl təsdiq)

- [x] Bir tələbə yalnız bir gün qrupunda olurmu? → cədvəl günündən qrup törədilir
- [x] 8 dərs həmişə eyni müəllimlədir, yoxsa kurs üzrə ümumi? → eyni müəllim–tələbə
- [x] Maaş hər 8 dərsdə, yoxsa ay sonunda toplanıb verilir? → vahidlər toplanır, ay paneli ilə hesabat

---

## Fayllar

- `src/All/muellim/attendance_helpers.php`
- `src/All/qr_muellim/panels.php`
- `src/All/qr_muellim/live.php`
- `src/All/qr_muellim/export.php`
- `src/All/qr_muellim/print_qr.php`
- `src/All/qr_muellim.php` (yeniləndi)
- `src/All/qr_telebe.php` (8/8 mesaj)

AZN tarif: `ATT_SALARY_RATE_AZN` (`attendance_helpers.php`) — default `0` (yalnız vahid göstərilir).
