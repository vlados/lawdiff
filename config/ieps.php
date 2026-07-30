<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Keyword patterns
    |--------------------------------------------------------------------------
    |
    | PCRE patterns matched against every node's `text_markdown` across the
    | whole corpus.
    |
    | Case-insensitive by design, not by necessity. ЗДвП does contain a
    | sentence-initial "Индивидуално електрическо превозно средство" (the §6
    | definition in ДОПЪЛНИТЕЛНИ РАЗПОРЕДБИ), but that node also carries a
    | lowercase occurrence, so today the two variants select the same 34 nodes.
    | The `i` guards the case where a future provision opens a sentence with the
    | term and never repeats it — which no keyword would otherwise reach.
    |
    | Explicit Cyrillic classes rather than \w. Not a correctness requirement —
    | PHP's /u modifier enables UCP, so \w does match Cyrillic — but \w would
    | also match Latin letters and digits, and these patterns should only ever
    | grow Bulgarian inflectional endings.
    |
    | "ИЕПС" and "тротинетка" match nothing today; they are practice and media
    | terms rather than statute language. They are kept so the export picks
    | them up if the legislature ever adopts them.
    |
    */

    'keywords' => [
        '/индивидуалн[а-я]*\s+електрическ[а-я]*/iu',
        '/\bИЕПС\b/u',
        '/тротинетк[а-я]*/iu',
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual path allowlist
    |--------------------------------------------------------------------------
    |
    | Keyed by the law's slug in the public export (data/index.json).
    |
    | SEMANTICS: each entry is a path PREFIX. It pulls in that node, its FULL
    | SUBTREE, and its ANCESTOR CHAIN — exactly what a keyword hit does. Keep
    | entries at article level or narrower: allowlisting "ДОП" would drag in
    | roughly 130 unrelated §1 definitions, which is the precise bloat the
    | subtree-plus-ancestors rule exists to avoid.
    |
    | Every path here must resolve to a real node. One that does not is a hard
    | error, not a warning — that is how article renumbering gets caught.
    |
    | ЗДвП ЧЛ80А — the ИЕПС article. Keyword matching alone drops ал. 8, ал. 10
    |   and ал. 6 т. 3, all of which refer to their own subject indirectly as
    |   "регистъра по ал. 5" instead of naming it. Those are the registry rules,
    |   i.e. the most important provisions in the dataset.
    |
    | Deliberately NOT included: ЗЕУ ЧЛ52А, the information system the registry
    | is built on (ЗДвП чл. 80а, ал. 5). It is e-government plumbing rather than
    | ИЕПС regulation, so that cross-reference is left dangling on purpose.
    |
    */

    'include' => [
        'zakon-za-dvizhenieto-po-patishtata' => ['ЧЛ80А'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Floor guard
    |--------------------------------------------------------------------------
    |
    | The daily workflow commits and pushes unattended. Without a floor, a
    | restructured source or a stalled fetch would quietly commit a gutted
    | dataset to main. Below any of these counts the command fails the job.
    |
    | Current real-world figures: 1 law, 77 provisions, 34 keyword matches.
    |
    | `matched` guards the keyword layer on its own. Without it, a total collapse
    | of keyword matching would still export ~48 nodes, because the allowlisted
    | ЧЛ80А subtree alone accounts for that many — enough to look healthy against
    | a node count on its own.
    |
    */

    'floor' => [
        'laws' => 1,
        'nodes' => 60,
        'matched' => 20,
    ],

];
