# Columns with many indexes

You *can*, but it’s almost never useful — and MySQL will happily allow it **as long as each index has a different name**.

### Short answer
Yes, MySQL allows multiple FULLTEXT indexes on the same column(s).  
There’s no technical restriction preventing it.

### But here’s the catch
Each FULLTEXT index is a **complete inverted index**.  
If you duplicate them:

- You double (or triple…) the storage cost  
- You increase write/update overhead  
- You gain **no performance benefit**  
- MySQL will **not** choose between them — they are functionally identical

So MySQL will let you do this:

```sql
ALTER TABLE wp_products ADD FULLTEXT ft_name_1 (product_name);
ALTER TABLE wp_products ADD FULLTEXT ft_name_2 (product_name);
ALTER TABLE wp_products ADD FULLTEXT ft_name_3 (product_name);
```

…but it’s pointless unless the indexes cover **different column combinations**.

### When multiple FULLTEXT indexes *do* make sense
Only in these cases:

| Index | Purpose |
|-------|---------|
| `FULLTEXT(product_name)` | Fast prefix search on names only |
| `FULLTEXT(product_name, product_short_description, expanded_description)` | Broader semantic search across all text fields |
| `FULLTEXT(expanded_description)` | If you want to weight this field differently or query it alone |

So the *useful* pattern is **different scopes**, not duplicates.

### Recommendation for your schema
For your WordPress-style product search, the ideal setup is:

- One composite index for the main search  
- Optional single-column indexes only if you run **separate, narrow searches** on those fields


MySQL’s index‑selection logic is surprisingly smart, but also very predictable once you know how the optimizer thinks. When multiple indexes *could* be used, MySQL runs a cost‑based decision process to choose the one that will return the result **fastest**.

Here’s a clear breakdown of how it decides.

---

## How MySQL chooses between multiple indexes

### 1. It estimates “cost” for each possible index
MySQL’s optimizer evaluates:

- How many rows the index will likely match  
- How selective the index is (fewer matches = better)  
- Whether the index can satisfy the query without touching the table  
- Whether the index can be used for sorting or grouping  
- Whether the index covers all columns needed (a *covering index*)  
- Whether the index supports the type of search (BTREE vs FULLTEXT)

It then picks the lowest‑cost plan.


## FULLTEXT indexes have their own rules
Since you’re working with FULLTEXT, here’s the key insight:

### If a query uses `MATCH() AGAINST()`, MySQL will ALWAYS choose a FULLTEXT index.

If multiple FULLTEXT indexes exist:

- MySQL chooses the one whose column list **exactly matches** the `MATCH()` list  
- If several match, it picks the one with the **fewest columns**  
- If still tied, it picks the one with the **lowest internal cost** (usually the smallest index)

So:

```sql
MATCH(product_name) AGAINST('shoes')
```

→ uses `FULLTEXT(product_name)` if it exists  
→ NOT the composite index unless the single‑column index is missing

But:

```sql
MATCH(product_name, product_short_description, expanded_description)
AGAINST('shoes')
```

→ uses the composite index only  
→ single‑column indexes are ignored


## Example: You have these indexes

- `FULLTEXT(product_name)`
- `FULLTEXT(product_name, product_short_description, expanded_description)`

### Query 1:
```sql
SELECT * FROM wp_products
WHERE MATCH(product_name) AGAINST('red dress');
```

**Chosen index:** `FULLTEXT(product_name)`  
Because it matches the column list exactly.

### Query 2:
```sql
SELECT * FROM wp_products
WHERE MATCH(product_name, product_short_description, expanded_description)
      AGAINST('red dress');
```

**Chosen index:** `FULLTEXT(product_name, product_short_description, expanded_description)`  
Because it’s the only one that covers all columns.

---

## Want to see what MySQL is doing?
Use:

```sql
EXPLAIN SELECT ...
```

or the more detailed:

```sql
EXPLAIN FORMAT=JSON SELECT ...
```

This will show exactly which index MySQL picked and why.



## Practical advice for your schema
Given your setup, the optimal pattern is:

- **One composite FULLTEXT index** for your main search  
- **Optional single‑column FULLTEXT indexes** only if you run *separate* searches on those fields

If you tell me the exact search queries your WordPress plugin will run, I can tell you the minimal set of indexes that gives maximum performance without redundancy.

<br>