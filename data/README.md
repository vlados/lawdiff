# Bulgarian Laws — Open Dataset

Generated daily from APIS.BG and committed to this repository.

- `index.json` — machine-readable manifest of every exported law
- `index.csv` — same manifest as a spreadsheet-friendly CSV
- `laws/<slug>.json` — one file per law containing metadata + structured node tree

Each law file includes the structured tree of articles, paragraphs, and items
as parsed by `App\Services\LawTreeProcessor`, with text rendered as Markdown.

**Total laws:** 369

## Consuming the data

```bash
# Single law (no clone needed)
curl https://raw.githubusercontent.com/<user>/lawdiff/main/data/laws/<slug>.json

# All laws — clone and iterate
jq '.laws[] | {slug, caption}' data/index.json
```

## Source

Bulgarian legislation data via [legislation.apis.bg](https://legislation.apis.bg/).
The structured tree representation is derived locally; raw text remains the work
of the Bulgarian state and stands in the public domain.
