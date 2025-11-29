import json
import os
from datetime import datetime

POSTS_DIR = "posts"

files = sorted([f for f in os.listdir(POSTS_DIR) if f.endswith('.json')])

print("Checking date distribution...")
print(f"Total posts: {len(files)}\n")

# Check first, middle, and last posts
check_indices = [0, len(files)//2, len(files)-1]

for idx in check_indices:
    filename = files[idx]
    filepath = os.path.join(POSTS_DIR, filename)
    with open(filepath, 'r', encoding='utf-8') as f:
        post_data = json.load(f)
    date = post_data.get('date', 'N/A')
    title = post_data.get('title', 'N/A')[:50]
    print(f"{filename}: {date} - {title}...")

