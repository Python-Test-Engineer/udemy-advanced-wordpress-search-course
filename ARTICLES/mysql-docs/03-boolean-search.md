# 14.9.2 Boolean Full-Text Searches

MySQL can perform boolean full-text searches using the IN BOOLEAN MODE modifier. With this modifier, certain characters have special meaning at the beginning or end of words in the search string. In the following query, the + and - operators indicate that a word must be present or absent, respectively, for a match to occur. Thus, the query retrieves all the rows that contain the word “MySQL” but that do not contain the word “YourSQL”:

```
SELECT * FROM articles WHERE MATCH (title,body)
AGAINST ('+MySQL -YourSQL' IN BOOLEAN MODE);
```

```
+----+-----------------------+-------------------------------------+
| id | title                 | body                                |
+----+-----------------------+-------------------------------------+
|  1 | MySQL Tutorial        | DBMS stands for DataBase ...        |
|  2 | How To Use MySQL Well | After you went through a ...        |
|  3 | Optimizing MySQL      | In this tutorial, we show ...       |
|  4 | 1001 MySQL Tricks     | 1. Never run mysqld as root. 2. ... |
|  6 | MySQL Security        | When configured properly, MySQL ... |
+----+-----------------------+-------------------------------------+
```


## AI generated queries

```sql
-- 1. REQUIRED WORD: Find rows containing "MySQL"
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+MySQL' IN BOOLEAN MODE);

-- 2. EXCLUDE WORD: Find rows with "database" but NOT "MySQL"
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+database -MySQL' IN BOOLEAN MODE);

-- 3. MULTIPLE REQUIRED WORDS: Must contain both "MySQL" AND "tutorial"
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+MySQL +tutorial' IN BOOLEAN MODE);

-- 4. EITHER/OR: Contains "MySQL" OR "database"
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('MySQL database' IN BOOLEAN MODE);

-- 5. EXACT PHRASE: Find exact phrase "configured properly"
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('"configured properly"' IN BOOLEAN MODE);

-- 6. WILDCARD: Words starting with "data" (database, data, etc.)
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('data*' IN BOOLEAN MODE);

-- 7. WORD MUST BE PRESENT, ANOTHER OPTIONAL: "MySQL" required, "security" increases rank
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+MySQL security' IN BOOLEAN MODE);

-- 8. COMPLEX: "MySQL" required, prefer "tutorial", exclude "tricks"
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+MySQL tutorial -tricks' IN BOOLEAN MODE);

-- 9. DECREASE RANK: "MySQL" required, downrank if contains "tricks"
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+MySQL ~tricks' IN BOOLEAN MODE);

-- 10. PHRASE WITH REQUIRED WORD: "MySQL" AND exact phrase "properly configured"
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+MySQL +"properly configured"' IN BOOLEAN MODE);

-- 11. MULTIPLE PHRASES: Either phrase must match
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('"MySQL Tutorial" "MySQL Security"' IN BOOLEAN MODE);

-- 12. WILDCARD WITH EXCLUSION: Words starting with "optim" but not "optimizer"
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+optim* -optimizer' IN BOOLEAN MODE);

-- 13. GROUPED LOGIC: (MySQL OR database) AND tutorial
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+(MySQL database) +tutorial' IN BOOLEAN MODE);

-- 14. PROXIMITY-LIKE: Required words with phrase bonus
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+MySQL +configured "MySQL configured"' IN BOOLEAN MODE);

-- 15. INCREASE RELEVANCE: Make "security" highly weighted
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+MySQL +security >security' IN BOOLEAN MODE);
```

## Boolean Operators Summary:

+ - Word MUST be present
- - Word MUST NOT be present
(no operator) - Word is optional (increases relevance if present)
> - Increases word's contribution to relevance
< - Decreases word's contribution to relevance
~ - Negates word's contribution (downranks if present)
* - Wildcard (matches words starting with prefix)
"..." - Exact phrase match
() - Group words into subexpressions

InnoDB full-text search does not support the use of the @ symbol in boolean full-text searches. The @ symbol is reserved for use by the @distance proximity search operator.

## @distance

```sql
-- 1. EXACT PHRASE: Words must be adjacent (distance = 0)
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('"MySQL Tutorial"' IN BOOLEAN MODE);
-- Matches: "MySQL Tutorial"
-- Doesn't match: "MySQL Advanced Tutorial"

-- 2. DISTANCE OF 1: Allow 1 word between
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('"MySQL Tutorial" @1' IN BOOLEAN MODE);
-- Matches: "MySQL Tutorial", "MySQL Advanced Tutorial"
-- Doesn't match: "MySQL Database Management Tutorial"

-- 3. DISTANCE OF 3: Allow up to 3 words between
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('"MySQL security" @3' IN BOOLEAN MODE);
-- Matches: "MySQL security"
-- Matches: "MySQL and database security"
-- Matches: "MySQL server installation and security"
-- Doesn't match: "MySQL is a powerful database management system with excellent security"

-- 4. MULTIPLE TERMS WITH DISTANCE
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('"properly configured" @2' IN BOOLEAN MODE);
-- Matches: "properly configured"
-- Matches: "properly and correctly configured"
-- Matches: "properly MySQL configured" (2 words max between)

-- 5. COMBINING WITH OTHER OPERATORS
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('+MySQL +"configured properly" @3' IN BOOLEAN MODE);
-- "MySQL" required anywhere
-- "configured properly" within 3 words distance

-- 6. ORDER MATTERS: Distance respects word order
SELECT * FROM articles 
WHERE MATCH(title, body) AGAINST('"MySQL tutorial" @2' IN BOOLEAN MODE);
-- Matches: "MySQL beginner tutorial"
-- Doesn't match: "tutorial for MySQL" (wrong order)
```

The wildcarded word is considered as a prefix that must be present at the start of one or more words. If the minimum word length is 4, a search for '+word +the*' could return fewer rows than a search for '+word +the', because the second query ignores the too-short search term the.

InnoDB uses a variation of the “term frequency-inverse document frequency” (TF-IDF) weighting system to rank a document's relevance for a given full-text search query. The TF-IDF weighting is based on how frequently a word appears in a document, offset by how frequently the word appears in all documents in the collection. In other words, the more frequently a word appears in a document, and the less frequently the word appears in the document collection, the higher the document is ranked.

## How Relevancy Ranking is Calculated

The term frequency (TF) value is the number of times that a word appears in a document. The inverse document frequency (IDF) value of a word is calculated using the following formula, where total_records is the number of records in the collection, and matching_records is the number of records that the search term appears in.

```
${IDF} = log10( ${total_records} / ${matching_records} )
When a document contains a word multiple times, the IDF value is multiplied by the TF value:

${TF} * ${IDF}
Using the TF and IDF values, the relevancy ranking for a document is calculated using this formula:

${rank} = ${TF} * ${IDF} * ${IDF}
The formula is demonstrated in the following examples.

Relevancy Ranking for a Single Word Search
This example demonstrates the relevancy ranking calculation for a single-word search.
```

```sql
CREATE TABLE articles (
  id INT UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
  title VARCHAR(200),
  body TEXT,
  FULLTEXT (title,body)
 )  ENGINE=InnoDB;
```
Query OK, 0 rows affected (1.04 sec)

```sql
INSERT INTO articles (title,body) VALUES
('MySQL Tutorial','This database tutorial ...'),
("How To Use MySQL",'After you went through a ...'),
('Optimizing Your Database','In this database tutorial ...'),
('MySQL vs. YourSQL','When comparing databases ...'),
('MySQL Security','When configured properly, MySQL ...'),
('Database, Database, Database','database database database'),
('1001 MySQL Tricks','1. Never run mysqld as root. 2. ...'),
('MySQL Full-Text Indexes', 'MySQL fulltext indexes use a ..');
```

Query OK, 8 rows affected (0.06 sec)
Records: 8  Duplicates: 0  Warnings: 0

```sql
SELECT id, title, body, 
MATCH (title,body) AGAINST ('database' IN BOOLEAN MODE) AS score
FROM articles ORDER BY score DESC;
```

```
+----+------------------------------+-------------------------------------+---------------------+
| id | title                        | body                                | score               |
+----+------------------------------+-------------------------------------+---------------------+
|  6 | Database, Database, Database | database database database          |  1.0886961221694946 |
|  3 | Optimizing Your Database     | In this database tutorial ...       | 0.36289870738983154 |
|  1 | MySQL Tutorial               | This database tutorial ...          | 0.18144935369491577 |
|  2 | How To Use MySQL             | After you went through a ...        |                   0 |
|  4 | MySQL vs. YourSQL            | When comparing databases ...        |                   0 |
|  5 | MySQL Security               | When configured properly, MySQL ... |                   0 |
|  7 | 1001 MySQL Tricks            | 1. Never run mysqld as root. 2. ... |                   0 |
|  8 | MySQL Full-Text Indexes      | MySQL fulltext indexes use a ..     |                   0 |
+----+------------------------------+-------------------------------------+---------------------+
8 rows in set (0.00 sec)
```

There are 8 records in total, with 3 that match the “database” search term. The first record (id 6) contains the search term 6 times and has a relevancy ranking of 1.0886961221694946. This ranking value is calculated using a TF value of 6 (the “database” search term appears 6 times in record id 6) and an IDF value of 0.42596873216370745, which is calculated as follows (where 8 is the total number of records and 3 is the number of records that the search term appears in):

```
${IDF} = LOG10( 8 / 3 ) = 0.42596873216370745
The TF and IDF values are then entered into the ranking formula:

${rank} = ${TF} * ${IDF} * ${IDF}
Performing the calculation in the MySQL command-line client returns a ranking value of 1.088696164686938.
```

```sql
SELECT 6*LOG10(8/3)*LOG10(8/3);
```

```
+-------------------------+
| 6*LOG10(8/3)*LOG10(8/3) |
+-------------------------+
|       1.088696164686938 |
+-------------------------+
1 row in set (0.00 sec)
```

Note

You may notice a slight difference in the ranking values returned by the SELECT ... MATCH ... AGAINST statement and the MySQL command-line client (1.0886961221694946 versus 1.088696164686938). The difference is due to how the casts between integers and floats/doubles are performed internally by InnoDB (along with related precision and rounding decisions), and how they are performed elsewhere, such as in the MySQL command-line client or other types of calculators.

## Relevancy Ranking for a Multiple Word Search
This example demonstrates the relevancy ranking calculation for a multiple-word full-text search based on the articles table and data used in the previous example.

If you search on more than one word, the relevancy ranking value is a sum of the relevancy ranking value for each word, as shown in this formula:

```
${rank} = ${TF} * ${IDF} * ${IDF} + ${TF} * ${IDF} * ${IDF}
Performing a search on two terms ('mysql tutorial') returns the following results:
```

```sql
SELECT id, title, body, MATCH (title,body)  
AGAINST ('mysql tutorial' IN BOOLEAN MODE) AS score
FROM articles ORDER BY score DESC;
```

```
+----+------------------------------+-------------------------------------+----------------------+
| id | title                        | body                                | score                |
+----+------------------------------+-------------------------------------+----------------------+
|  1 | MySQL Tutorial               | This database tutorial ...          |   0.7405621409416199 |
|  3 | Optimizing Your Database     | In this database tutorial ...       |   0.3624762296676636 |
|  5 | MySQL Security               | When configured properly, MySQL ... | 0.031219376251101494 |
|  8 | MySQL Full-Text Indexes      | MySQL fulltext indexes use a ..     | 0.031219376251101494 |
|  2 | How To Use MySQL             | After you went through a ...        | 0.015609688125550747 |
|  4 | MySQL vs. YourSQL            | When comparing databases ...        | 0.015609688125550747 |
|  7 | 1001 MySQL Tricks            | 1. Never run mysqld as root. 2. ... | 0.015609688125550747 |
|  6 | Database, Database, Database | database database database          |                    0 |
+----+------------------------------+-------------------------------------+----------------------+
8 rows in set (0.00 sec)
```

In the first record (id 8), 'mysql' appears once and 'tutorial' appears twice. There are six matching records for 'mysql' and two matching records for 'tutorial'. The MySQL command-line client returns the expected ranking value when inserting these values into the ranking formula for a multiple word search:

```sql
SELECT (1*log10(8/6)*log10(8/6)) + (2*log10(8/2)*log10(8/2));
```

```
+-------------------------------------------------------+
| (1*log10(8/6)*log10(8/6)) + (2*log10(8/2)*log10(8/2)) |
+-------------------------------------------------------+
|                                    0.7405621541938003 |
+-------------------------------------------------------+
1 row in set (0.00 sec)
```

### Note

The slight difference in the ranking values returned by the SELECT ... MATCH ... AGAINST statement and the MySQL command-line client is explained in the preceding example.

<br>