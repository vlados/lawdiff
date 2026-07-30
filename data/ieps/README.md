# ИЕПС — Индивидуални електрически превозни средства

Topical fork of the [main dataset](../README.md), regenerated daily alongside it.

Every provision of Bulgarian legislation that regulates индивидуални електрически
превозни средства (individual electric vehicles — e-scooters and similar).

- `index.json` — manifest, plus the exact criteria used to select provisions
- `index.csv` — same manifest as CSV
- `laws/<slug>.json` — the ИЕПС provisions of one law

Slugs match the main dataset. Each entry carries a `full_text` pointer to the
complete law at `data/laws/<slug>.json` (repo-relative).

**Laws:** 1 · **Provisions:** 77

## Selection rule

A node is selected when its text matches a keyword pattern, or when it is named in
the allowlist in `config/ieps.php`. Each selected node is exported together with
its **full subtree** and its **ancestor chain**, so every provision reads on its
own — a matched алинея carries its точки down and its член caption up.

The allowlist exists because keyword matching alone is not sufficient: чл. 80а,
ал. 8 and ал. 10 ЗДвП govern the ИЕПС registry itself but refer to their own
subject indirectly, as "регистъра по ал. 5", so no keyword can reach them.

## Scope

Derived from the same corpus as the main dataset — the Закони folder of
legislation.apis.bg — and reflecting the law currently in force.

**Not covered:** наредби, including the общински наредби under чл. 80а, ал. 4 ЗДвП
where much of the operative registration detail lives; кодекси; правилници. There
is no amendment overlay and no version history.

The `full_text` pointer on each law always resolves. What is not followed are
references *inside* the provision text: чл. 80а, ал. 5 invokes "системата по чл. 52а
от Закона за електронното управление", and the exporter does not pull that article
in — read it in the main dataset.
