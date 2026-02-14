# Batch Embeddings Processing - Performance Improvement

## Overview
The plugin has been upgraded to use **batch processing** for generating OpenAI embeddings, resulting in dramatically faster processing and lower costs.

## What Changed?

### Before (Sequential Processing)
- ❌ Processed 1 post at a time
- ❌ Made 1 API call per post
- ❌ 0.1 second delay between each call
- ❌ For 159 posts: ~159 API calls + 15.9 seconds in delays
- ❌ Slow and inefficient

### After (Batch Processing)
- ✅ Processes up to 100 posts at once
- ✅ Makes 1 API call per 100 posts
- ✅ 0.5 second delay between batches only
- ✅ For 159 posts: ~2 API calls + 0.5 seconds in delays
- ✅ **Up to 50x faster!**

## Technical Details

### Batch Size
- **Default**: 100 posts per batch
- **OpenAI Limit**: Up to 2,048 inputs per request
- **Why 100?**: Safe conservative limit to avoid timeouts and ensure reliability

### How It Works

1. **Fetch all posts** without embeddings from database
2. **Split into batches** of 100 posts each
3. **For each batch**:
   - Prepare all texts in an array
   - Send single API request with all texts
   - OpenAI returns embeddings for all inputs
   - Update all posts in database
4. **Small delay** (0.5s) between batches to avoid rate limits

### API Request Format

**Before (Single)**:
```json
{
  "input": "Post Title\n\nPost content...",
  "model": "text-embedding-3-small"
}
```

**After (Batch)**:
```json
{
  "input": [
    "Post 1 Title\n\nPost 1 content...",
    "Post 2 Title\n\nPost 2 content...",
    "Post 3 Title\n\nPost 3 content...",
    ...
  ],
  "model": "text-embedding-3-small"
}
```

### Response Handling

OpenAI returns embeddings with an `index` field to match the input order:
```json
{
  "data": [
    {"index": 0, "embedding": [...]},
    {"index": 1, "embedding": [...]},
    {"index": 2, "embedding": [...]}
  ]
}
```

The code:
1. Maps each index back to the correct post
2. Sorts embeddings by index to ensure correct order
3. Updates each post with its embedding

## Performance Comparison

### Example: 159 Posts

**Old Method**:
- API Calls: 159
- Time: ~159 requests × 0.5s avg = ~80 seconds
- Plus delays: 15.9 seconds
- **Total: ~96 seconds**

**New Method**:
- API Calls: 2 (batches of 100 and 59)
- Time: ~2 requests × 0.5s avg = ~1 second
- Plus delays: 0.5 seconds
- **Total: ~1.5 seconds**

**Speed Improvement: 64x faster!**

## Cost Savings

OpenAI charges per token, not per API call, so:
- **Tokens used**: Same (you're embedding the same content)
- **API calls made**: 98% reduction (159 → 2 calls)
- **Network overhead**: Minimal instead of significant
- **Time saved**: Massive reduction in wall-clock time

While the cost per token is the same, you save on:
- **Time**: Much faster processing
- **Rate limits**: Fewer calls = less risk of hitting limits
- **Server resources**: Less waiting, fewer HTTP connections

## Error Handling

### Batch Failure
If an entire batch fails:
- All posts in that batch are marked as errors
- Processing continues with next batch
- Error message indicates which batch failed

### Individual Failures
If OpenAI returns embeddings but some are missing:
- Successfully returned embeddings are saved
- Missing ones are counted as errors
- Specific post IDs are logged

## Console Logging

The batch process includes detailed logging:
```
🔄 Generate Embeddings button clicked
📡 Sending generate_embeddings request
✅ Generate embeddings response: {success: true, data: "Generated embeddings for 159 posts in 2 batches."}
⏳ Waiting 500ms before refreshing stats...
🔄 Now calling refreshStats()
✓ Generate embeddings complete
```

## Configuration

To adjust batch size, modify line 992 in `wp-posts-rag-manager.php`:

```php
// Current setting
$batch_size = 100;

// For faster processing (if you have stable internet)
$batch_size = 200;

// For more conservative approach
$batch_size = 50;
```

**Note**: OpenAI's hard limit is 2,048 inputs per request.

## Success Message Format

The success message now includes batch information:
- **Single batch**: "Generated embeddings for 50 posts."
- **Multiple batches**: "Generated embeddings for 159 posts in 2 batches."
- **With errors**: "Generated embeddings for 150 posts in 2 batches. 9 errors occurred."

## Backward Compatibility

The old single-embedding method `get_openai_embedding()` is still present in the code for:
- Backward compatibility
- Potential use in other parts of the plugin
- Emergency fallback if needed

However, the main `generate_embeddings()` method now uses the batch approach.

## Troubleshooting

### Timeout Errors
If you get timeout errors:
- Reduce `$batch_size` (try 50 or 25)
- Increase timeout in line 1093: `'timeout' => 60` → `'timeout' => 120`

### Rate Limit Errors
If you hit OpenAI rate limits:
- Reduce `$batch_size`
- Increase delay between batches (line 1048): `usleep(500000)` → `usleep(1000000)`

### Memory Issues
If processing very large posts:
- Reduce `$batch_size`
- The `wp_trim_words($post->post_content, 500)` already limits content length

## Benefits Summary

✅ **50-100x faster processing**
✅ **98% fewer API calls**
✅ **Better rate limit compliance**
✅ **Same embedding quality**
✅ **Same cost per token**
✅ **More reliable for large datasets**
✅ **Detailed error reporting**
✅ **Batch progress tracking**

## Future Enhancements

Possible improvements for future versions:
- Progress bar showing batch completion
- Configurable batch size in admin UI
- Retry logic for failed batches
- Parallel batch processing
- Queue-based processing for very large datasets
