# Term Frequency (TF) 

## What is Term Frequency?

Imagine you're looking for your favorite toy in a messy room. The more times you see that toy's name on boxes, the more likely that box has what you want! That's exactly how Term Frequency works in search engines.

**Term Frequency (TF)** = How many times a word appears in a document

## Why Do We Care?

Search engines like Google need to figure out which documents (web pages) are most relevant to your search. If you search for "python programming", a document that mentions "python" 20 times is probably more relevant than one that mentions it only once!

## How It Works - The Simple Formula


The basic Term Frequency (TF) formula divides a word's count in a document by the total number of words in that document, representing its relative importance, with variations like raw count, logarithmic scaling, or augmented frequency used to prevent bias towards longer texts.


### Example Time! 

Let's say we have two documents and we're searching for the word **"cat"**:

**Document 1:** (10 words total)
"I love cats. Cats are amazing. Cats make me happy."
- Word "cat" appears: 3 times
- TF = 3/10 = 0.3

**Document 2:** (15 words total)
"I have a cat. My pet is nice. I walk my pet daily."
- Word "cat" appears: 0 times
- TF = 1/15 = 0.067

**Winner:** Document 1 has higher TF for "cat"! 🏆

## Visual Comparison

```
Document A: "dog dog dog cat"
🐕🐕🐕🐱
TF(dog) = 3/4 = 0.75

Document B: "dog cat cat cat"  
🐕🐱🐱🐱
TF(dog) = 3/4 = 0.25
```

## Real-World Search Example

**Your Search:** "machine learning"

**Document 1:** Blog post about AI
- "machine" appears 5 times
- "learning" appears 5 times
- Total words: 100
- TF(machine) = 0.05, TF(learning) = 0.05

**Document 2:** Tutorial on machine learning
- "machine" appears 25 times
- "learning" appears 30 times
- Total words: 200
- TF(machine) = 0.125, TF(learning) = 0.15

**Result:** Document 2 likely more relevant! ✅

We can have a weighted formula like TF(machine) x TF(learning) or [TF(machine) + 2 x TF(learning)].

## Problem

![tf](../images/fts/tf-saturation.png)

What about 'word stuffing'? A good document with a lower TF should rank higher than a poor document with word stuffing.

## Quick Summary

- **Higher TF** = Word appears more often = Probably more relevant
- **Lower TF** = Word appears less often = Probably less relevant
- **Formula:** TF = (Word count in doc) / (Total words in doc)
- **Used in:** Search engines, recommendation systems, text analysis

<br>

