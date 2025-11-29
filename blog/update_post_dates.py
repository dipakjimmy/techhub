#!/usr/bin/env python3
"""
Script to update dates for all blog posts (1-700)
Distribute dates evenly from 2023-01-01 to today
"""
import json
import os
from datetime import datetime, timedelta

POSTS_DIR = "posts"

def update_all_post_dates():
    """Update dates for all posts, distributing them evenly from 2023 to today"""
    
    # Date range
    start_date = datetime(2023, 1, 1)
    end_date = datetime.now()  # Today
    total_days = (end_date - start_date).days
    
    # Get all JSON files and sort by filename (which should be numbered)
    files = []
    for filename in os.listdir(POSTS_DIR):
        if filename.endswith('.json'):
            filepath = os.path.join(POSTS_DIR, filename)
            # Extract number from filename (e.g., "001-", "201-", etc.)
            try:
                # Get the number prefix
                num_str = filename.split('-')[0]
                if num_str.isdigit():
                    post_num = int(num_str)
                    files.append((post_num, filepath, filename))
            except:
                continue
    
    # Sort by post number
    files.sort(key=lambda x: x[0])
    
    total_posts = len(files)
    print(f"Found {total_posts} posts to update")
    print(f"Date range: {start_date.strftime('%Y-%m-%d')} to {end_date.strftime('%Y-%m-%d')}")
    print(f"Total days: {total_days}\n")
    
    updated_count = 0
    
    for idx, (post_num, filepath, filename) in enumerate(files):
        try:
            # Read the post
            with open(filepath, 'r', encoding='utf-8') as f:
                post_data = json.load(f)
            
            # Calculate date for this post
            # Distribute evenly across the date range
            if total_posts > 1:
                days_offset = int((total_days * idx) / (total_posts - 1))
            else:
                days_offset = 0
            
            new_date = start_date + timedelta(days=days_offset)
            new_date_str = new_date.strftime('%Y-%m-%d')
            
            # Update the date
            old_date = post_data.get('date', 'N/A')
            post_data['date'] = new_date_str
            
            # Write back
            with open(filepath, 'w', encoding='utf-8') as f:
                json.dump(post_data, f, indent=4, ensure_ascii=False)
            
            updated_count += 1
            
            if (idx + 1) % 100 == 0:
                print(f"Updated {idx + 1}/{total_posts} posts...")
                
        except Exception as e:
            print(f"Error updating {filename}: {e}")
            continue
    
    print(f"\nSuccessfully updated {updated_count} posts!")
    print(f"Date range: {start_date.strftime('%Y-%m-%d')} to {end_date.strftime('%Y-%m-%d')}")

if __name__ == "__main__":
    if not os.path.exists(POSTS_DIR):
        print(f"Error: {POSTS_DIR} directory not found!")
    else:
        update_all_post_dates()

