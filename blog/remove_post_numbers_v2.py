#!/usr/bin/env python3
"""
Script to remove number prefixes from post filenames and slugs
Uses the slug field as the filename to ensure uniqueness
"""
import json
import os
import shutil
from pathlib import Path

POSTS_DIR = "posts"
BACKUP_DIR = "posts_backup_v2"

def remove_numbers_from_posts():
    """Remove number prefixes from post filenames and update slugs"""
    
    if not os.path.exists(POSTS_DIR):
        print(f"Error: {POSTS_DIR} directory not found!")
        return
    
    # Create backup directory
    if os.path.exists(BACKUP_DIR):
        shutil.rmtree(BACKUP_DIR)
    os.makedirs(BACKUP_DIR, exist_ok=True)
    
    files = sorted([f for f in os.listdir(POSTS_DIR) if f.endswith('.json')])
    total_files = len(files)
    
    print(f"Processing {total_files} posts...")
    print("Creating backup first...\n")
    
    renamed_count = 0
    updated_count = 0
    slug_to_files = {}  # Track which files use which slug
    
    for filename in files:
        filepath = os.path.join(POSTS_DIR, filename)
        
        try:
            # Read the post
            with open(filepath, 'r', encoding='utf-8') as f:
                post_data = json.load(f)
            
            # Backup the original file
            backup_path = os.path.join(BACKUP_DIR, filename)
            shutil.copy2(filepath, backup_path)
            
            # Get current slug
            current_slug = post_data.get('slug', '')
            
            # Remove number prefix from slug if present
            if current_slug:
                slug_parts = current_slug.split('-', 1)
                if len(slug_parts) == 2 and slug_parts[0].isdigit():
                    new_slug = slug_parts[1]
                else:
                    # If slug doesn't have number, check if filename has number
                    file_parts = filename.split('-', 1)
                    if len(file_parts) == 2 and file_parts[0].isdigit():
                        # Use filename without number as slug
                        new_slug = file_parts[1].replace('.json', '')
                    else:
                        new_slug = current_slug
            else:
                # Generate slug from filename
                file_parts = filename.split('-', 1)
                if len(file_parts) == 2 and file_parts[0].isdigit():
                    new_slug = file_parts[1].replace('.json', '')
                else:
                    new_slug = filename.replace('.json', '')
            
            # Track slug usage
            if new_slug not in slug_to_files:
                slug_to_files[new_slug] = []
            slug_to_files[new_slug].append(filename)
            
            # Update slug in post data
            post_data['slug'] = new_slug
            
            # Determine new filename - use slug, but make unique if needed
            new_filename = new_slug + '.json'
            
            # If multiple files will have the same slug, append original number to make unique
            if len(slug_to_files[new_slug]) > 1:
                # Extract original number from filename
                file_parts = filename.split('-', 1)
                if len(file_parts) == 2 and file_parts[0].isdigit():
                    original_num = file_parts[0]
                    # Make filename unique by appending original number
                    new_filename = f"{new_slug}-{original_num}.json"
            
            new_filepath = os.path.join(POSTS_DIR, new_filename)
            
            # Write updated JSON to new filename
            with open(new_filepath, 'w', encoding='utf-8') as f:
                json.dump(post_data, f, indent=4, ensure_ascii=False)
            
            # Delete old file only if it's different from new file
            if filepath != new_filepath:
                os.remove(filepath)
            
            renamed_count += 1
            updated_count += 1
            
            if renamed_count % 100 == 0:
                print(f"Processed {renamed_count}/{total_files} posts...")
                
        except Exception as e:
            print(f"Error processing {filename}: {e}")
            continue
    
    print(f"\nSuccessfully processed {renamed_count} files")
    print(f"Successfully updated {updated_count} slugs")
    print(f"Backup saved to: {BACKUP_DIR}")
    
    # Report on duplicates
    duplicates = {k: v for k, v in slug_to_files.items() if len(v) > 1}
    if duplicates:
        print(f"\nNote: {len(duplicates)} slugs have multiple posts (kept unique with number suffix)")

if __name__ == "__main__":
    remove_numbers_from_posts()

