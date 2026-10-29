<?php

// Generated from dzkit-spec by bin/generate. Do not edit by hand: update spec/ and run
// `php bin/generate`. Each value links back to a rule in https://github.com/dzkit/dzkit-spec,
// where its official sources, verification level and validity dates are recorded.

declare(strict_types=1);

namespace Dzkit\Generated;

/** @internal */
final class SpecData
{
    public const SPEC_VERSION = '0.4.0';

    // administrative.wilayas (valid from 1984-02-04; sources: loi-84-09, decret-84-79, loi-19-12, decret-presidentiel-21-117, loi-26-06, decret-presidentiel-26-206, upgrowth-wilayas-2026, wikipedia-provinces)
    public const WILAYAS_VALID_FROM = '1984-02-04';
    public const WILAYAS = [
        ['code' => 1, 'name' => 'Adrar', 'name_ar' => 'أدرار'],
        ['code' => 2, 'name' => 'Chlef', 'name_ar' => 'الشلف'],
        ['code' => 3, 'name' => 'Laghouat', 'name_ar' => 'الأغواط'],
        ['code' => 4, 'name' => 'Oum El Bouaghi', 'name_ar' => 'أم البواقي'],
        ['code' => 5, 'name' => 'Batna', 'name_ar' => 'باتنة'],
        ['code' => 6, 'name' => 'Béjaïa', 'name_ar' => 'بجاية'],
        ['code' => 7, 'name' => 'Biskra', 'name_ar' => 'بسكرة'],
        ['code' => 8, 'name' => 'Béchar', 'name_ar' => 'بشار'],
        ['code' => 9, 'name' => 'Blida', 'name_ar' => 'البليدة'],
        ['code' => 10, 'name' => 'Bouira', 'name_ar' => 'البويرة'],
        ['code' => 11, 'name' => 'Tamanrasset', 'name_ar' => 'تمنراست'],
        ['code' => 12, 'name' => 'Tébessa', 'name_ar' => 'تبسة'],
        ['code' => 13, 'name' => 'Tlemcen', 'name_ar' => 'تلمسان'],
        ['code' => 14, 'name' => 'Tiaret', 'name_ar' => 'تيارت'],
        ['code' => 15, 'name' => 'Tizi Ouzou', 'name_ar' => 'تيزي وزو'],
        ['code' => 16, 'name' => 'Alger', 'name_ar' => 'الجزائر'],
        ['code' => 17, 'name' => 'Djelfa', 'name_ar' => 'الجلفة'],
        ['code' => 18, 'name' => 'Jijel', 'name_ar' => 'جيجل'],
        ['code' => 19, 'name' => 'Sétif', 'name_ar' => 'سطيف'],
        ['code' => 20, 'name' => 'Saïda', 'name_ar' => 'سعيدة'],
        ['code' => 21, 'name' => 'Skikda', 'name_ar' => 'سكيكدة'],
        ['code' => 22, 'name' => 'Sidi Bel Abbès', 'name_ar' => 'سيدي بلعباس'],
        ['code' => 23, 'name' => 'Annaba', 'name_ar' => 'عنابة'],
        ['code' => 24, 'name' => 'Guelma', 'name_ar' => 'قالمة'],
        ['code' => 25, 'name' => 'Constantine', 'name_ar' => 'قسنطينة'],
        ['code' => 26, 'name' => 'Médéa', 'name_ar' => 'المدية'],
        ['code' => 27, 'name' => 'Mostaganem', 'name_ar' => 'مستغانم'],
        ['code' => 28, 'name' => 'M\'Sila', 'name_ar' => 'المسيلة'],
        ['code' => 29, 'name' => 'Mascara', 'name_ar' => 'معسكر'],
        ['code' => 30, 'name' => 'Ouargla', 'name_ar' => 'ورقلة'],
        ['code' => 31, 'name' => 'Oran', 'name_ar' => 'وهران'],
        ['code' => 32, 'name' => 'El Bayadh', 'name_ar' => 'البيض'],
        ['code' => 33, 'name' => 'Illizi', 'name_ar' => 'إليزي'],
        ['code' => 34, 'name' => 'Bordj Bou Arréridj', 'name_ar' => 'برج بوعريريج'],
        ['code' => 35, 'name' => 'Boumerdès', 'name_ar' => 'بومرداس'],
        ['code' => 36, 'name' => 'El Tarf', 'name_ar' => 'الطارف'],
        ['code' => 37, 'name' => 'Tindouf', 'name_ar' => 'تندوف'],
        ['code' => 38, 'name' => 'Tissemsilt', 'name_ar' => 'تيسمسيلت'],
        ['code' => 39, 'name' => 'El Oued', 'name_ar' => 'الوادي'],
        ['code' => 40, 'name' => 'Khenchela', 'name_ar' => 'خنشلة'],
        ['code' => 41, 'name' => 'Souk Ahras', 'name_ar' => 'سوق أهراس'],
        ['code' => 42, 'name' => 'Tipaza', 'name_ar' => 'تيبازة'],
        ['code' => 43, 'name' => 'Mila', 'name_ar' => 'ميلة'],
        ['code' => 44, 'name' => 'Aïn Defla', 'name_ar' => 'عين الدفلى'],
        ['code' => 45, 'name' => 'Naâma', 'name_ar' => 'النعامة'],
        ['code' => 46, 'name' => 'Aïn Témouchent', 'name_ar' => 'عين تموشنت'],
        ['code' => 47, 'name' => 'Ghardaïa', 'name_ar' => 'غرداية'],
        ['code' => 48, 'name' => 'Relizane', 'name_ar' => 'غليزان'],
        ['code' => 49, 'name' => 'Timimoun', 'name_ar' => 'تيميمون', 'parent' => 1, 'valid_from' => '2019-12-11'],
        ['code' => 50, 'name' => 'Bordj Badji Mokhtar', 'name_ar' => 'برج باجي مختار', 'parent' => 1, 'valid_from' => '2019-12-11'],
        ['code' => 51, 'name' => 'Ouled Djellal', 'name_ar' => 'أولاد جلال', 'parent' => 7, 'valid_from' => '2019-12-11'],
        ['code' => 52, 'name' => 'Béni Abbès', 'name_ar' => 'بني عباس', 'parent' => 8, 'valid_from' => '2019-12-11'],
        ['code' => 53, 'name' => 'In Salah', 'name_ar' => 'عين صالح', 'parent' => 11, 'valid_from' => '2019-12-11'],
        ['code' => 54, 'name' => 'In Guezzam', 'name_ar' => 'عين قزام', 'parent' => 11, 'valid_from' => '2019-12-11'],
        ['code' => 55, 'name' => 'Touggourt', 'name_ar' => 'تقرت', 'parent' => 30, 'valid_from' => '2019-12-11'],
        ['code' => 56, 'name' => 'Djanet', 'name_ar' => 'جانت', 'parent' => 33, 'valid_from' => '2019-12-11'],
        ['code' => 57, 'name' => 'El M\'Ghair', 'name_ar' => 'المغير', 'parent' => 39, 'valid_from' => '2019-12-11'],
        ['code' => 58, 'name' => 'El Menia', 'name_ar' => 'المنيعة', 'parent' => 47, 'valid_from' => '2019-12-11'],
        ['code' => 59, 'name' => 'Aflou', 'name_ar' => 'آفلو', 'parent' => 3, 'valid_from' => '2026-04-04'],
        ['code' => 60, 'name' => 'Barika', 'name_ar' => 'بريكة', 'parent' => 5, 'valid_from' => '2026-04-04'],
        ['code' => 61, 'name' => 'El Kantara', 'name_ar' => 'القنطرة', 'parent' => 7, 'valid_from' => '2026-04-04'],
        ['code' => 62, 'name' => 'Bir El Ater', 'name_ar' => 'بئر العاتر', 'parent' => 12, 'valid_from' => '2026-04-04'],
        ['code' => 63, 'name' => 'El Aricha', 'name_ar' => 'العريشة', 'parent' => 13, 'valid_from' => '2026-04-04'],
        ['code' => 64, 'name' => 'Ksar Chellala', 'name_ar' => 'قصر الشلالة', 'parent' => 14, 'valid_from' => '2026-04-04'],
        ['code' => 65, 'name' => 'Aïn Ouessara', 'name_ar' => 'عين وسارة', 'parent' => 17, 'valid_from' => '2026-04-04'],
        ['code' => 66, 'name' => 'Messaad', 'name_ar' => 'مسعد', 'parent' => 17, 'valid_from' => '2026-04-04'],
        ['code' => 67, 'name' => 'Ksar El Boukhari', 'name_ar' => 'قصر البخاري', 'parent' => 26, 'valid_from' => '2026-04-04'],
        ['code' => 68, 'name' => 'Bou Saâda', 'name_ar' => 'بوسعادة', 'parent' => 28, 'valid_from' => '2026-04-04'],
        ['code' => 69, 'name' => 'El Abiodh Sidi Cheikh', 'name_ar' => 'الأبيض سيدي الشيخ', 'parent' => 32, 'valid_from' => '2026-04-04'],
    ];

    // vehicles.plates (sources: arrete-plates-2026, decret-presidentiel-26-206)
    public const PLATE_WILAYA_MAX_BEFORE = 58;
    public const PLATE_WILAYA_MAX = 69;
    public const PLATE_NEW_WILAYAS_FROM = '2027-01-01';
    public const PLATE_SERIAL_DISPLAY_DIGITS = 5;
    public const PLATE_CATEGORIES = [
        1 => ['fr' => 'Véhicule de tourisme', 'ar' => 'سيارة سياحية', 'en' => 'Passenger car'],
        2 => ['fr' => 'Camion', 'ar' => 'شاحنة', 'en' => 'Truck'],
        3 => ['fr' => 'Camionnette', 'ar' => 'شاحنة صغيرة', 'en' => 'Van'],
        4 => ['fr' => 'Autocar ou autobus', 'ar' => 'حافلة', 'en' => 'Coach or bus'],
        5 => ['fr' => 'Tracteur routier', 'ar' => 'جرار طريق', 'en' => 'Road tractor'],
        6 => ['fr' => 'Autre tracteur', 'ar' => 'جرار آخر', 'en' => 'Other tractor'],
        7 => ['fr' => 'Véhicule spécial', 'ar' => 'مركبة خاصة', 'en' => 'Special vehicle'],
        8 => ['fr' => 'Remorque ou semi-remorque', 'ar' => 'مقطورة أو نصف مقطورة', 'en' => 'Trailer or semi-trailer'],
        9 => ['fr' => 'Motocyclette', 'ar' => 'دراجة نارية', 'en' => 'Motorcycle'],
    ];

    // telecom.phone (valid from 2008-02-22; sources: arpce-pnn)
    public const PHONE_COUNTRY_CODE = '213';
    public const PHONE_OPERATORS = [5 => 'Ooredoo', 6 => 'Mobilis', 7 => 'Djezzy'];
    public const PHONE_MOBILE_BLOCKS = ['Ooredoo' => [[540, 542], [549, 549], [550, 559], [560, 562]], 'Mobilis' => [[655, 659], [660, 669], [670, 676], [696, 699]], 'Djezzy' => [[770, 779], [780, 784], [790, 799]]];
    public const PHONE_FREEPHONE_BLOCKS = [[800, 800]];
    public const PHONE_SERVICE_BLOCKS = [[800, 800], [801, 806], [808, 809]];
    public const PHONE_VOIP_BLOCKS = [[961, 961], [982, 982]];

    // banking.ccp-rip (sources: babalweb-rip, experts-univers-rip, excel-downloads-rip, mouh2020-algeria)
    public const RIP_BANK_CODE = '007';
    public const RIP_BRANCH_CODE = '99999';
    public const RIP_ACCOUNT_DIGITS = 10;
    public const RIP_KEY = ['modulus' => 97, 'multiplier' => 100, 'offset' => 85];
    public const CCP_KEY_WEIGHTS_FROM_RIGHT = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13];
    public const CCP_KEY_MODULUS = 100;

    // business.nif (status confirmed; sources: python-stdnum-nif, upgrowth-nif)
    public const NIF_LENGTHS = [15, 20];
    // business.nis (status official; sources: decret-executif-97-396)
    public const NIS_LENGTHS = [15, 18];

    // payroll.irg-salary (valid from 2022-01-01; sources: loi-21-16, cidta-2026, dgi-bareme-irg)
    public const IRG_VALID_FROM = '2022-01-01';
    public const IRG_REGIMES = ['salaried', 'retired', 'disabled'];
    /** Monthly brackets: [upper bound in DA or null for no limit, rate]. */
    public const IRG_MONTHLY_BRACKETS = [[20000, 0], [40000, 0.23], [80000, 0.27], [160000, 0.3], [320000, 0.33], [null, 0.35]];
    public const IRG_EXEMPTION_THRESHOLD = 30000;
    public const IRG_ABATEMENT = ['rate' => 0.4, 'min' => 1000, 'max' => 1500];
    public const IRG_SALARIED_SMOOTHING = ['below' => 35000, 'multiplier' => [137, 51], 'subtract' => [27925, 8]];
    public const IRG_FAVOURABLE_SMOOTHING = ['below' => 42500, 'multiplier' => [93, 61], 'subtract' => [81213, 41]];
    public const IRG_ROUNDING_STEP = 0.1;

    // payroll.cnas (sources: cnas-employer, cleiss-algerie)
    public const CNAS_EMPLOYEE_RATE = 0.09;
    public const CNAS_EMPLOYER_RATE = 0.25;
    public const CNAS_SOCIAL_SERVICES_RATE = 0.005;
}
