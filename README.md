**English** | [Français](README.fr.md) | [العربية](README.ar.md)

# dzkit for PHP 🇩🇿

[![CI](https://github.com/dzkit/dzkit-php/actions/workflows/ci.yml/badge.svg)](https://github.com/dzkit/dzkit-php/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/dzkit/dzkit-php.svg)](https://packagist.org/packages/dzkit/dzkit-php)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

The small things every Algerian app ends up rewriting, tested once and shared:

- **CCP / RIP**: Algérie Poste keys, RIP generation and validation
- **Phone numbers**: parse, validate, format, detect the operator
- **NIF / NIS**: format checks
- **Licence plates**: parse and validate, including the new wilaya codes 59–69
- **69 wilayas**: codes, French and Arabic names, creation dates
- **Amounts in words**: DZD in French and Arabic, for invoices
- **Payroll**: IRG (article 104) and CNAS, gross ↔ net, payslip breakdown

PHP 8.1+, no dependencies. The same rules and the same results as [dzkit for JavaScript](https://github.com/dzkit/dzkit-js): both follow [dzkit-spec](https://github.com/dzkit/dzkit-spec) and pass all of its 339 shared test cases.

**See what it does:** try every function in the [online playground](https://dzkit.github.io/dzkit-js/). It runs the JavaScript package, which gives the same results as this one.

```bash
composer require dzkit/dzkit-php
```

## CCP and RIP

```php
use Dzkit\Ccp;
use Dzkit\Rip;

Rip::fromCcp('23115007');                 // '00799999002311500797'
Rip::isValid('007 99999 0023115007 97');  // true
Rip::format('00799999002311500797');      // '007 99999 0023115007 97'
Ccp::key('1234567890');                   // '45' (the key written after the CCP number)
Ccp::isValid('1234567890', '45');         // true
Rip::parse('00799999123456789006');       // ParsedRip { account: '1234567890', key: '06', ccpKey: '45', … }
```

Tested against real RIP numbers, including key `97`, which many calculators get wrong (they return `00`).

## Phone numbers

```php
use Dzkit\Phone;

$p = Phone::parse('+213 551 23 45 67');
$p->type;      // 'mobile'
$p->operator;  // 'Ooredoo'
$p->national;  // '0551234567'
$p->e164;      // '+213551234567'

Phone::operator('0661234567');                        // 'Mobilis'
Phone::format('0771234567', Phone::INTERNATIONAL);    // '+213 771 23 45 67'
Phone::isValid('021 23 45 67', type: Phone::LANDLINE); // true
```

05 = Ooredoo, 06 = Mobilis, 07 = Djezzy, 08 = freephone and value-added services, 09 = VoIP, 02x/03x/04x = landline. The operator comes from the prefix; Algeria has no mobile number portability today.

`allocated` tells you whether the number is in a block ARPCE has actually assigned (for example, Mobilis has 0655–0676 and 0696–0699, so 0685… doesn't exist yet). Validation doesn't require it by default, so a newly assigned block keeps working; pass `allocatedOnly: true` to be strict:

```php
Phone::parse('0685 12 34 56')->allocated;                   // false
Phone::isValidMobile('0685 12 34 56', allocatedOnly: true);  // false
```

## NIF and NIS

```php
use Dzkit\Nif;
use Dzkit\Nis;

Nif::isValid('408 020 000 150 039');  // true  (15 digits, or 20 for a branch)
Nis::isValid('098716010002343');      // true  (15 digits, or 18 for a secondary establishment)
```

No public check-digit algorithm exists for these, so this checks the format only. To confirm a NIF exists, use the DGI portal (nifenligne.mf.gov.dz).

## Licence plates

```php
use Dzkit\Plate;

$plate = Plate::parse('02639-126-09');
$plate->serial;        // '02639'
$plate->category;      // 1
$plate->categoryName;  // 'Véhicule de tourisme'
$plate->year;          // '26'
$plate->wilaya->name;  // 'Blida'

Plate::parse('12345-127-64', '2026-10-28');            // null: code 64 isn't issued before 2027
Plate::parse('12345-127-64', '2027-03-01')->wilaya->name; // 'Ksar Chellala'
```

Codes 59–69 are only accepted from 1 January 2027, when the new wilayas get their plates. The date is today in Algeria by default; pass a `DateTimeInterface` or `'YYYY-MM-DD'` to check another day.

## Wilayas

```php
use Dzkit\Wilaya;

$w = Wilaya::get(16);  // Wilaya { code: 16, name: 'Alger', nameAr: 'الجزائر' }
Wilaya::get('016');    // the same; '16a' or 16.5 give null
count(Wilaya::all());  // 69

Wilaya::get(60, '2026-04-03');          // null: Barika became a wilaya on 4 April 2026
Wilaya::get(60)->parent;                // 5 (created from Batna)
```

Need communes, dairas or postal codes? This package covers wilayas only for now.

## Amounts in words

```php
use Dzkit\Words;

Words::amount(1250.5);        // 'mille deux cent cinquante dinars algériens et cinquante centimes'
Words::amount(1250.5, 'ar');  // 'ألف ومائتان وخمسون دينارا جزائريا وخمسون سنتيما'

Words::toWordsFr(80_000_000); // 'quatre-vingts millions'
Words::toWordsAr(3000);       // 'ثلاثة آلاف'
Words::amountAr(2000);        // 'ألفا دينار جزائري'
Words::amountAr(50000);       // 'خمسون ألف دينار جزائري'
```

French follows the traditional agreement rules (quatre-vingts, deux cents, mille invariable, « un million de dinars »). Arabic applies the counted-noun rules (دينار واحد، ديناران، ثلاثة دنانير، خمسة عشر دينارا) and the construct form before the currency (مائتا دينار، ألفا دينار، خمسون ألف دينار). Amounts are rounded half up to the centime, on the amount as written: `1.005` gives one centime.

## Payroll: IRG and CNAS

```php
use Dzkit\Payroll\Irg;
use Dzkit\Payroll\Salary;

$s = Salary::grossToNet(60_000);
// SalaryResult { gross: 60000, cnasEmployee: 5400, taxable: 54600, irg: 7042, net: 47558,
//                cnasEmployer: 15300, employerCost: 75300 }

Irg::calculate(40_000);              // 3100.0
Irg::calculate(40_000, Irg::RETIRED); // 2745.4
Irg::breakdown(32_000);              // every step, for payslips: scaleIrg 2760, abatement 1104, smoothing 698.15, irg 957.8
Salary::netToGross(47_558)->gross;   // 59999.89: the smallest gross that gives this net
```

What it implements (article 104 of the CIDTA, scale from the 2022 finance law, unchanged for salaries in 2026):

| Monthly taxable income (DA) | Rate |
|---|---|
| 0 – 20 000 | 0 % |
| 20 001 – 40 000 | 23 % |
| 40 001 – 80 000 | 27 % |
| 80 001 – 160 000 | 30 % |
| 160 001 – 320 000 | 33 % |
| above 320 000 | 35 % |

- Taxable income up to 30 000 DA: exempt
- 40 % abatement on the tax, bounded between 1 000 and 1 500 DA
- 30 000–35 000 DA: second abatement, `IRG × 137/51 − 27 925/8`
- Retirees (régime général) and disabled workers, 30 000–42 500 DA: `IRG × 93/61 − 81 213/41`
- The scale result is rounded to the centime, then the IRG is rounded down to the tenth of a dinar, like the DGI table. All of it is computed with integers, so there is no floating-point error
- CNAS: 9 % employee, 25 % + 0.5 % employer (both overridable)

Out of scope for now: non-monthly bonuses (10 % flat withholding), exempt allowances, and the training and apprenticeship taxes.

## Input, errors and dates

The same rules apply in every dzkit package, from [dzkit-spec's OPERATIONS.md](https://github.com/dzkit/dzkit-spec/blob/main/OPERATIONS.md):

- **Validators, parsers and formatters never throw.** Pass them whatever a form, a file or a database contains: anything invalid, including `null`, an array, a number instead of text or an empty string, gives `false` or `null`.
- **Arabic-Indic digits are accepted everywhere**: `Phone::parse('٠٥٥١٢٣٤٥٦٧')` works, and so do RIPs, plates, NIF/NIS and wilaya codes.
- **Whole-number floats count as integers**: `Wilaya::get(16.0)` is Alger, as databases and JSON often give `16.0`.
- **Calculations throw `Dzkit\DzkitException`** with an `errorCode` when the request is impossible, rather than guessing:

```php
use Dzkit\DzkitException;

try {
    Irg::calculate(40_000, 'retraite'); // a typo
} catch (DzkitException $e) {
    $e->errorCode; // 'invalid-option' (DzkitException::INVALID_OPTION)
}
```

| `errorCode` | When |
|---|---|
| `invalid-amount` | negative, not a number (text such as `'40000'` too), or too large (amounts in words stop at 999 999 999 999.99 DA) |
| `invalid-option` | an unknown regime, language, phone type or style, or date format |
| `no-rule-for-date` | the date is before the first recorded rule (the IRG scale starts in 2022) |

- **Dates:** functions whose rule depends on the day take `$at`, a `DateTimeInterface` or `'YYYY-MM-DD'`, and default to today in Algeria (UTC+1): `Irg::calculate`, `Irg::breakdown`, `Salary::grossToNet`, `Salary::netToGross`, `Plate::parse`, `Wilaya::get`.

`Dzkit\Dzkit::SPEC_VERSION` tells which dzkit-spec version the data comes from.

## Sources

Every rule comes from an official text, ranked by the hierarchy of Algerian legal texts. The rules, their sources and the evidence behind each one are kept in [dzkit-spec](https://github.com/dzkit/dzkit-spec), shared by every dzkit package; see its [trust report](https://github.com/dzkit/dzkit-spec/blob/main/TRUST.md).

## Not legal advice

This is reference material, maintained with care and linked to its sources so you can check every value. It is not legal or tax advice. When a value matters, check it against the official text, and please [open an issue](https://github.com/dzkit/dzkit-spec/issues) if you find a difference.

## Contributing

Issues and pull requests are welcome in العربية, Français or English. See [CONTRIBUTING.md](CONTRIBUTING.md) and the project's [governance](https://github.com/dzkit/dzkit-spec/blob/main/GOVERNANCE.md).

Especially useful:

- A real example that fails (a RIP, a plate, an IRG amount from a payslip)
- A rule change from a new finance law (in [dzkit-spec](https://github.com/dzkit/dzkit-spec/issues), so every language gets it)
- The Laravel and Symfony integrations, built on this package

## License

[MIT](LICENSE) © Siroukane and the dzkit contributors. Official texts belong to their publishers.
