# 14.9.1 Natural Language Full-Text Searches

By default or with the IN NATURAL LANGUAGE MODE modifier, the MATCH() function performs a natural language search for a string against a text collection. A collection is a set of one or more columns included in a FULLTEXT index. The search string is given as the argument to AGAINST(). For each row in the table, MATCH() returns a relevance value; that is, a similarity measure between the search string and the text in that row in the columns named in the MATCH() list.

```
CREATE TABLE articles (
 id INT UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
 title VARCHAR(200),
 body TEXT,
 FULLTEXT (title,body)
) ENGINE=InnoDB;
```

Query OK, 0 rows affected (0.08 sec)

```
INSERT INTO articles (title,body) VALUES
 ('MySQL Tutorial','DBMS stands for DataBase ...'),
 ('How To Use MySQL Well','After you went through a ...'),
 ('Optimizing MySQL','In this tutorial, we show ...'),
 ('1001 MySQL Tricks','1. Never run mysqld as root. 2. ...'),
 ('MySQL vs. YourSQL','In the following database comparison ...'),
 ('MySQL Security','When configured properly, MySQL ...');
```


Query OK, 6 rows affected (0.01 sec)

Records: 6  Duplicates: 0  Warnings: 0

```
SELECT * FROM articles
WHERE MATCH (title,body)
AGAINST ('database' IN NATURAL LANGUAGE MODE);
```

```
+----+-------------------+------------------------------------------+
| id | title             | body                                     |
+----+-------------------+------------------------------------------+
|  1 | MySQL Tutorial    | DBMS stands for DataBase ...             |
|  5 | MySQL vs. YourSQL | In the following database comparison ... |
+----+-------------------+------------------------------------------+
2 rows in set (0.00 sec)
```

By default, the search is performed in case-insensitive fashion. To perform a case-sensitive full-text search, use a case-sensitive or binary collation for the indexed columns. For example, a column that uses the utf8mb4 character set of can be assigned a collation of utf8mb4_0900_as_cs or utf8mb4_bin to make it case-sensitive for full-text searches.

When MATCH() is used in a WHERE clause, as in the example shown earlier, the rows returned are automatically sorted with the highest relevance first as long as the following conditions are met:

There must be no explicit ORDER BY clause.

The search must be performed using a full-text index scan rather than a table scan.

If the query joins tables, the full-text index scan must be the leftmost non-constant table in the join.

Given the conditions just listed, it is usually less effort to specify using ORDER BY an explicit sort order when one is necessary or desired.

Relevance values are nonnegative floating-point numbers. Zero relevance means no similarity. Relevance is computed based on the number of words in the row (document), the number of unique words in the row, the total number of words in the collection, and the number of rows that contain a particular word.

Note
The term “document” may be used interchangeably with the term “row”, and both terms refer to the indexed part of the row. The term “collection” refers to the indexed columns and encompasses all rows.

To simply count matches, you could use a query like this:

```
SELECT COUNT(*) FROM articles
WHERE MATCH (title,body)
AGAINST ('database' IN NATURAL LANGUAGE MODE);
```
```
+----------+
| COUNT(*) |
+----------+
|        2 |
+----------+
1 row in set (0.00 sec)
```
You might find it quicker to rewrite the query as follows:

```
SELECT
COUNT(IF(MATCH (title,body) AGAINST ('database' IN NATURAL LANGUAGE MODE), 1, NULL))
AS count
FROM articles;
```
```
+-------+
| count |
+-------+
|     2 |
+-------+
1 row in set (0.03 sec)
```
The first query does some extra work (sorting the results by relevance) but also can use an index lookup based on the WHERE clause. The index lookup might make the first query faster if the search matches few rows. The second query performs a full table scan, which might be faster than the index lookup if the search term was present in most rows.

For natural-language full-text searches, the columns named in the MATCH() function must be the same columns included in some FULLTEXT index in your table. For the preceding query, note that the columns named in the MATCH() function (title and body) are the same as those named in the definition of the article table's FULLTEXT index. To search the title or body separately, you would create separate FULLTEXT indexes for each column.

You can also perform a boolean search or a search with query expansion. These search types are described in Section 14.9.2, “Boolean Full-Text Searches”, and Section 14.9.3, “Full-Text Searches with Query Expansion”.

A full-text search that uses an index can name columns only from a single table in the MATCH() clause because an index cannot span multiple tables. For MyISAM tables, a boolean search can be done in the absence of an index (albeit more slowly), in which case it is possible to name columns from multiple tables.

The preceding example is a basic illustration that shows how to use the MATCH() function where rows are returned in order of decreasing relevance. The next example shows how to retrieve the relevance values explicitly. Returned rows are not ordered because the SELECT statement includes neither WHERE nor ORDER BY clauses:

```
SELECT id, MATCH (title,body)
AGAINST ('Tutorial' IN NATURAL LANGUAGE MODE) AS score
FROM articles;
```
```
+----+---------------------+
| id | score               |
+----+---------------------+
|  1 | 0.22764469683170319 |
|  2 |                   0 |
|  3 | 0.22764469683170319 |
|  4 |                   0 |
|  5 |                   0 |
|  6 |                   0 |
+----+---------------------+
6 rows in set (0.00 sec)
```

The following example is more complex. The query returns the relevance values and it also sorts the rows in order of decreasing relevance. To achieve this result, specify MATCH() twice: once in the SELECT list and once in the WHERE clause. This causes no additional overhead, because the MySQL optimizer notices that the two MATCH() calls are identical and invokes the full-text search code only once.

```
SELECT id, body, MATCH (title,body)
AGAINST ('Security implications of running MySQL as root'
IN NATURAL LANGUAGE MODE) AS score
FROM articles
WHERE MATCH (title,body) 
AGAINST('Security implications of running MySQL as root'
IN NATURAL LANGUAGE MODE);
```
```
+----+-------------------------------------+-----------------+
| id | body                                | score           |
+----+-------------------------------------+-----------------+
|  4 | 1. Never run mysqld as root. 2. ... | 1.5219271183014 |
|  6 | When configured properly, MySQL ... | 1.3114095926285 |
+----+-------------------------------------+-----------------+
2 rows in set (0.00 sec)
```

A phrase that is enclosed within double quote (") characters matches only rows that contain the phrase literally, as it was typed. The full-text engine splits the phrase into words and performs a search in the FULLTEXT index for the words. Nonword characters need not be matched exactly: Phrase searching requires only that matches contain exactly the same words as the phrase and in the same order. For example, "test phrase" matches "test, phrase". If the phrase contains no words that are in the index, the result is empty. For example, if all words are either stopwords or shorter than the minimum length of indexed words, the result is empty.

The MySQL FULLTEXT implementation regards any sequence of true word characters (letters, digits, and underscores) as a word. That sequence may also contain apostrophes ('), but not more than one in a row. This means that aaa'bbb is regarded as one word, but aaa''bbb is regarded as two words. Apostrophes at the beginning or the end of a word are stripped by the FULLTEXT parser; 'aaa'bbb' would be parsed as aaa'bbb.

The built-in FULLTEXT parser determines where words start and end by looking for certain delimiter characters; for example,   (space), , (comma), and . (period). If words are not separated by delimiters (as in, for example, Chinese), the built-in FULLTEXT parser cannot determine where a word begins or ends. To be able to add words or other indexed terms in such languages to a FULLTEXT index that uses the built-in FULLTEXT parser, you must preprocess them so that they are separated by some arbitrary delimiter. Alternatively, you can create FULLTEXT indexes using the ngram parser plugin (for Chinese, Japanese, or Korean) or the MeCab parser plugin (for Japanese).

It is possible to write a plugin that replaces the built-in full-text parser. For details, see The MySQL Plugin API. For example parser plugin source code, see the plugin/fulltext directory of a MySQL source distribution.

Some words are ignored in full-text searches:

Any word that is too short is ignored. The default minimum length of words that are found by full-text searches is three characters for InnoDB search indexes, or four characters for MyISAM. You can control the cutoff by setting a configuration option before creating the index: innodb_ft_min_token_size configuration option for InnoDB search indexes, or ft_min_word_len for MyISAM.

Note
This behavior does not apply to FULLTEXT indexes that use the ngram parser. For the ngram parser, token length is defined by the ngram_token_size option.

Words in the stopword list are ignored. A stopword is a word such as “the” or “some” that is so common that it is considered to have zero semantic value. There is a built-in stopword list, but it can be overridden by a user-defined list. The stopword lists and related configuration options are different for InnoDB search indexes and MyISAM ones. Stopword processing is controlled by the configuration options innodb_ft_enable_stopword, innodb_ft_server_stopword_table, and innodb_ft_user_stopword_table for InnoDB search indexes, and ft_stopword_file for MyISAM ones.

See Section 14.9.4, “Full-Text Stopwords” to view default stopword lists and how to change them. The default minimum word length can be changed as described in Section 14.9.6, “Fine-Tuning MySQL Full-Text Search”.

Every correct word in the collection and in the query is weighted according to its significance in the collection or query. Thus, a word that is present in many documents has a lower weight, because it has lower semantic value in this particular collection. Conversely, if the word is rare, it receives a higher weight. The weights of the words are combined to compute the relevance of the row. This technique works best with large collections.

MyISAM Limitation
For very small tables, word distribution does not adequately reflect their semantic value, and this model may sometimes produce bizarre results for search indexes on MyISAM tables. For example, although the word “MySQL” is present in every row of the articles table shown earlier, a search for the word in a MyISAM search index produces no results:

```
SELECT * FROM articles
WHERE MATCH (title,body)
AGAINST ('MySQL' IN NATURAL LANGUAGE MODE);
```

Empty set (0.00 sec)

The search result is empty because the word “MySQL” is present in at least 50% of the rows, and so is effectively treated as a stopword. This filtering technique is more suitable for large data sets, where you might not want the result set to return every second row from a 1GB table, than for small data sets where it might cause poor results for popular terms.

The 50% threshold can surprise you when you first try full-text searching to see how it works, and makes InnoDB tables more suited to experimentation with full-text searches. If you create a MyISAM table and insert only one or two rows of text into it, every word in the text occurs in at least 50% of the rows. As a result, no search returns any results until the table contains more rows. Users who need to bypass the 50% limitation can build search indexes on InnoDB tables, or use the boolean search mode explained in Section 14.9.2, “Boolean Full-Text Searches”.

<br>