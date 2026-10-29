[English](README.md) | **Français** | [العربية](README.ar.md)

# dzkit pour PHP 🇩🇿

Les petites fonctions que chaque application algérienne finit par réécrire, testées une fois et partagées :

- **CCP / RIP** : clés Algérie Poste, génération et vérification du RIP
- **Numéros de téléphone** : analyse, validation, formatage, opérateur
- **NIF / NIS** : vérification du format
- **Plaques d'immatriculation** : y compris les nouveaux codes de wilaya 59 à 69
- **69 wilayas** : codes, noms en français et en arabe, dates de création
- **Montants en lettres** : en dinars, en français et en arabe, pour les factures
- **Paie** : IRG (article 104) et CNAS, brut ↔ net, détail pour la fiche de paie

PHP 8.1+, aucune dépendance. Les mêmes règles et les mêmes résultats que [dzkit pour JavaScript](https://github.com/dzkit/dzkit-js) : les deux suivent [dzkit-spec](https://github.com/dzkit/dzkit-spec) et passent ses 339 cas de test communs.

```bash
composer require dzkit/dzkit-php
```

## Exemples

```php
use Dzkit\{Ccp, Nif, Phone, Plate, Rip, Wilaya, Words};
use Dzkit\Payroll\{Irg, Salary};

Rip::fromCcp('23115007');                 // '00799999002311500797'
Rip::isValid('007 99999 0023115007 97');  // true
Ccp::key('1234567890');                   // '45'

Phone::operator('0661234567');            // 'Mobilis'
Nif::isValid('408 020 000 150 039');      // true
Plate::parse('02639-126-09')->wilaya->name; // 'Blida'
Wilaya::get(64)->name;                    // 'Ksar Chellala'

Words::amount(1250.5);
// 'mille deux cent cinquante dinars algériens et cinquante centimes'

Salary::grossToNet(60_000);
// SalaryResult { cnasEmployee: 5400, taxable: 54600, irg: 7042, net: 47558, cnasEmployer: 15300, … }
Irg::calculate(40_000, Irg::RETIRED);     // 2745.4
```

## Règles appliquées

**RIP** : `007 99999` + numéro CCP sur 10 chiffres + clé. Vérifié sur de vrais RIP, y compris la clé `97`, que beaucoup de calculateurs renvoient à tort comme `00`.

**NIF / NIS** : aucun algorithme de clé n'est publié, seule la forme est vérifiée (NIF : 15 ou 20 chiffres ; NIS : 15 ou 18 chiffres). Pour confirmer qu'un NIF existe : nifenligne.mf.gov.dz.

**Plaques** : les codes 59 à 69 ne sont acceptés qu'à partir du 1er janvier 2027 (paramètre `$at` pour une autre date).

**IRG** (article 104 du CIDTA, barème de la LF 2022, inchangé pour les salaires en 2026) :
- exonération jusqu'à 30 000 DA de revenu imposable par mois ;
- abattement de 40 % de l'impôt, entre 1 000 et 1 500 DA ;
- de 30 000 à 35 000 DA : `IRG × 137/51 − 27 925/8` ;
- retraités du régime général et travailleurs handicapés, de 30 000 à 42 500 DA : `IRG × 93/61 − 81 213/41` ;
- barème arrondi au centime, puis IRG arrondi au dixième de dinar inférieur, comme le barème de la DGI ; tout est calculé en nombres entiers, sans erreur d'arrondi.

**CNAS** : 9 % salarié, 25 % + 0,5 % employeur (modifiables).

**Saisies, erreurs et dates** (règles communes à tous les paquets dzkit, voir [OPERATIONS.md](https://github.com/dzkit/dzkit-spec/blob/main/OPERATIONS.md)) :
- les fonctions de validation et de lecture ne lèvent jamais d'exception : une valeur invalide, même `null`, un tableau ou un nombre au lieu d'un texte, donne `false` ou `null` ;
- les chiffres arabes orientaux (٠١٢…) sont acceptés partout, et un nombre décimal entier comme `16.0` compte comme `16` ;
- les calculs lèvent une `Dzkit\DzkitException` avec un `errorCode` (`invalid-amount`, `invalid-option`, `no-rule-for-date`) au lieu de deviner, par exemple pour un régime inconnu ;
- `Irg::calculate`, `Salary::grossToNet`, `Plate::parse` et `Wilaya::get` acceptent `$at` : un `DateTimeInterface` ou `'AAAA-MM-JJ'` (par défaut, aujourd'hui en Algérie).

## Sources

Chaque règle repose sur un texte officiel, classé selon la hiérarchie des textes juridiques algériens. Les règles, leurs sources et les preuves de chacune sont tenues dans [dzkit-spec](https://github.com/dzkit/dzkit-spec), commun à tous les paquets dzkit ; voir son [rapport de confiance](https://github.com/dzkit/dzkit-spec/blob/main/TRUST.md).

## Pas un conseil juridique

Ce projet est une référence, maintenue avec soin et reliée à ses sources pour que chaque valeur puisse être vérifiée. Ce n'est pas un conseil juridique ou fiscal. Quand une valeur compte, vérifiez-la dans le texte officiel, et [ouvrez une issue](https://github.com/dzkit/dzkit-spec/issues) si vous trouvez une différence.

## Contribuer

Les issues et pull requests sont les bienvenues en العربية, français ou anglais. Voir [CONTRIBUTING.md](CONTRIBUTING.md) et la [gouvernance](https://github.com/dzkit/dzkit-spec/blob/main/GOVERNANCE.md) du projet.

Particulièrement utiles :

- Un exemple réel qui échoue (un RIP, une plaque, un montant d'IRG tiré d'une fiche de paie)
- Un changement de règle après une nouvelle loi de finances (dans [dzkit-spec](https://github.com/dzkit/dzkit-spec/issues), pour que tous les langages en profitent)
- Les intégrations Laravel et Symfony, construites sur ce paquet

## Licence

[MIT](LICENSE) © Siroukane et les contributeurs de dzkit. Les textes officiels appartiennent à leurs éditeurs.
