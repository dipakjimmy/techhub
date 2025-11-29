#!/usr/bin/env python3
"""
Script to remove number prefixes from post filenames and slugs
Example: 001-bim-fundamentals.json -> bim-fundamentals.json
"""
import json
import os
import shutil
from pathlib import Path

POSTS_DIR = "posts"
BACKUP_DIR = "posts_backup"

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
    
    for filename in files:
        filepath = os.path.join(POSTS_DIR, filename)
        
        try:
            # Read the post
            with open(filepath, 'r', encoding='utf-8') as f:
                post_data = json.load(f)
            
            # Backup the original file
            backup_path = os.path.join(BACKUP_DIR, filename)
            shutil.copy2(filepath, backup_path)
            
            # Extract number prefix (e.g., "001-" from "001-bim-fundamentals.json")
            parts = filename.split('-', 1)
            if len(parts) == 2 and parts[0].isdigit():
                # Remove number prefix from filename
                new_filename = parts[1]  # e.g., "bim-fundamentals.json"
                new_filepath = os.path.join(POSTS_DIR, new_filename)
                
                # Update slug in JSON data
                old_slug = post_data.get('slug', '')
                if old_slug:
                    # Remove number prefix from slug if present
                    slug_parts = old_slug.split('-', 1)
                    if len(slug_parts) == 2 and slug_parts[0].isdigit():
                        new_slug = slug_parts[1]
                    else:
                        # If slug doesn't have number, use filename without extension
                        new_slug = new_filename.replace('.json', '')
                else:
                    # Generate slug from filename
                    new_slug = new_filename.replace('.json', '')
                
                # Update slug in post data
                post_data['slug'] = new_slug
                
                # Write updated JSON to new filename
                with open(new_filepath, 'w', encoding='utf-8') as f:
                    json.dump(post_data, f, indent=4, ensure_ascii=False)
                
                # Delete old file
                os.remove(filepath)
                
                renamed_count += 1
                updated_count += 1
                
                if renamed_count % 100 == 0:
                    print(f"Processed {renamed_count}/{total_files} posts...")
            else:
                # File doesn't have number prefix, just update slug if needed
                slug = post_data.get('slug', '')
                if slug:
                    slug_parts = slug.split('-', 1)
                    if len(slug_parts) == 2 and slug_parts[0].isdigit():
                        new_slug = slug_parts[1]
                        post_data['slug'] = new_slug
                        
                        with open(filepath, 'w', encoding='utf-8') as f:
                            json.dump(post_data, f, indent=4, ensure_ascii=False)
                        
                        updated_count += 1
                
        except Exception as e:
            print(f"Error processing {filename}: {e}")
            continue
    
    print(f"\nSuccessfully processed {renamed_count} files (renamed)")
    print(f"Successfully updated {updated_count} slugs")
    print(f"Backup saved to: {BACKUP_DIR}")

if __name__ == "__main__":
    remove_numbers_from_posts()

