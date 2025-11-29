#!/usr/bin/env python3
"""Check for duplicate filenames after removing numbers"""
import os
import json
from collections import defaultdict

POSTS_DIR = "posts"
BACKUP_DIR = "posts_backup"

# Check backup directory for original filenames
backup_files = [f for f in os.listdir(BACKUP_DIR) if f.endswith('.json')]

# Group by what the filename would be after removing number prefix
name_map = defaultdict(list)

for filename in backup_files:
    parts = filename.split('-', 1)
    if len(parts) == 2 and parts[0].isdigit():
        new_name = parts[1]
        name_map[new_name].append(filename)

# Find duplicates
duplicates = {k: v for k, v in name_map.items() if len(v) > 1}

print(f"Total backup files: {len(backup_files)}")
print(f"Unique names after removing prefix: {len(name_map)}")
print(f"Duplicate names: {len(duplicates)}\n")

if duplicates:
    print("Sample duplicates (first 10):")
    for new_name, original_files in list(duplicates.items())[:10]:
        print(f"\n{new_name}:")
        for orig in original_files:
            # Read slug from backup
            backup_path = os.path.join(BACKUP_DIR, orig)
            try:
                with open(backup_path, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    slug = data.get('slug', 'N/A')
                    title = data.get('title', 'N/A')[:60]
                    print(f"  - {orig} -> slug: {slug}")
                    print(f"    Title: {title}")
            except:
                print(f"  - {orig}")

