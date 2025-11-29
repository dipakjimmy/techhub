#!/usr/bin/env python3
"""
Fix duplicate slugs by ensuring each post has a unique slug
For files with number suffix, update slug to include that suffix
"""
import json
import os
import re

POSTS_DIR = "posts"

def fix_duplicate_slugs():
    """Ensure each post has a unique slug"""
    
    files = sorted([f for f in os.listdir(POSTS_DIR) if f.endswith('.json')])
    total_files = len(files)
    
    print(f"Processing {total_files} posts to fix duplicate slugs...\n")
    
    updated_count = 0
    
    for filename in files:
        filepath = os.path.join(POSTS_DIR, filename)
        
        try:
            # Read the post
            with open(filepath, 'r', encoding='utf-8') as f:
                post_data = json.load(f)
            
            current_slug = post_data.get('slug', '')
            filename_base = filename.replace('.json', '')
            
            # Check if filename has a number suffix (e.g., "topic-441")
            match = re.match(r'^(.+)-(\d+)$', filename_base)
            if match:
                base_name, number = match.groups()
                # If slug doesn't match filename, update it
                if current_slug != filename_base:
                    post_data['slug'] = filename_base
                    updated_count += 1
            else:
                # No number suffix - slug should match filename
                if current_slug != filename_base:
                    post_data['slug'] = filename_base
                    updated_count += 1
            
            # Write back
            with open(filepath, 'w', encoding='utf-8') as f:
                json.dump(post_data, f, indent=4, ensure_ascii=False)
                
        except Exception as e:
            print(f"Error processing {filename}: {e}")
            continue
    
    print(f"Updated {updated_count} slugs to match filenames")

if __name__ == "__main__":
    fix_duplicate_slugs()

