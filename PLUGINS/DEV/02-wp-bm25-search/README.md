# BM25 Explained Like I'm 4 (ELI4)

Imagine you have a big pile of storybooks. You ask, **"Which book talks most about cats?"**

BM25 is like a **smart helper** that looks at all the books and decides which one is the best match for your question.

## The Kid-Friendly Idea

BM25 gives each book **points** based on three simple ideas:

### 1. **How many times the word shows up (but not *too* many)**
- If a book says **"cat"** a few times, that’s good.
- If it says **"cat"** 100 times, that’s **not** 100 times better.
- BM25 says: **"Okay, I get it, this book is about cats."**

### 2. **Short books vs long books**
- A short book that mentions **"cat"** twice might be more focused than a huge book that mentions it twice.
- BM25 gives a **little bonus** to shorter, more focused books.

### 3. **Rare words are special**
- If a word is **rare** (like "unicorn"), finding it is exciting.
- If a word is **common** (like "the"), it doesn’t mean much.
- BM25 gives **extra points** for rare words.

## The Simple Result
BM25 adds up the points and says:

✅ **This book is most about what you asked!**

---

## In This Plugin
The plugin does these steps:
1. **Splits every document into words**
2. **Counts how long each document is**
3. **Counts how often each word appears**
4. **Scores each document with BM25**
5. **Sorts results from best to worst**

That’s it! BM25 is just a smart way to **rank results** so the most helpful answer comes first.
