# Changelog

Versions follow the rule shared by every dzkit package (see [CONTRIBUTING.md](CONTRIBUTING.md#releasing)). Composer reads the version from the Git tag.

## 0.1.0 — 2026-10-29

First release. Follows [dzkit-spec 0.4.0](https://github.com/dzkit/dzkit-spec/releases/tag/v0.4.0) and passes all 339 of its shared test cases, with the same results as [dzkit-js](https://github.com/dzkit/dzkit-js) 0.4.0 (compared on more than 1,250,000 outputs, with no difference).

- **CCP and RIP**: `Rip::fromCcp`, `Rip::isValid`, `Rip::parse`, `Rip::format`, `Rip::key`, `Ccp::key`, `Ccp::isValid`
- **Phone numbers**: `Phone::parse`, `Phone::isValid` (by type, allocated blocks only), `Phone::isValidMobile`, `Phone::operator`, `Phone::format`
- **NIF and NIS**: `Nif::isValid`, `Nis::isValid`, `compact`
- **Licence plates**: `Plate::parse`, `Plate::isValid`, `Plate::format`, with wilaya codes 59–69 from 1 January 2027
- **Wilayas**: `Wilaya::get` (by date), `Wilaya::all`, with French and Arabic names, parent wilaya and creation date for 49–69
- **Amounts in words**: `Words::amount`, `amountFr`, `amountAr`, `toWordsFr`, `toWordsAr`
- **Payroll**: `Irg::calculate`, `Irg::breakdown`, `Salary::grossToNet`, `Salary::netToGross`, computed with integers only
- `DzkitException` with `errorCode` (`invalid-amount`, `invalid-option`, `no-rule-for-date`); validators, parsers and formatters never throw
- Arabic-Indic digits accepted everywhere; whole-number floats such as `16.0` count as integers
- `Dzkit::SPEC_VERSION`
