#!/usr/bin/env python3
"""
Script to generate XML files for blog posts:
1. sitemap.xml - for search engines
2. rss.xml - RSS feed for blog posts
"""
import json
import os
from datetime import datetime
from xml.etree.ElementTree import Element, SubElement, tostring
from xml.dom import minidom

# Base URL - adjust this to your actual domain
BASE_URL = "http://localhost/techpub/blog/"
POSTS_DIR = "posts"

def prettify_xml(elem):
    """Return a pretty-printed XML string for the Element."""
    rough_string = tostring(elem, encoding='unicode')
    reparsed = minidom.parseString(rough_string)
    return reparsed.toprettyxml(indent="  ")

def generate_sitemap():
    """Generate sitemap.xml for all blog posts"""
    urlset = Element('urlset')
    urlset.set('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9')
    
    # Add main blog page
    url = SubElement(urlset, 'url')
    SubElement(url, 'loc').text = BASE_URL
    SubElement(url, 'lastmod').text = datetime.now().strftime('%Y-%m-%d')
    SubElement(url, 'changefreq').text = 'daily'
    SubElement(url, 'priority').text = '1.0'
    
    # Get all JSON files
    files = sorted([f for f in os.listdir(POSTS_DIR) if f.endswith('.json')])
    
    for filename in files:
        filepath = os.path.join(POSTS_DIR, filename)
        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                post_data = json.load(f)
            
            slug = post_data.get('slug', filename.replace('.json', ''))
            post_url = BASE_URL + slug + '/'
            
            # Get file modification time
            mod_time = datetime.fromtimestamp(os.path.getmtime(filepath))
            
            url = SubElement(urlset, 'url')
            SubElement(url, 'loc').text = post_url
            SubElement(url, 'lastmod').text = mod_time.strftime('%Y-%m-%d')
            SubElement(url, 'changefreq').text = 'monthly'
            SubElement(url, 'priority').text = '0.8'
            
        except Exception as e:
            print(f"Error processing {filename}: {e}")
            continue
    
    # Generate XML string
    xml_string = prettify_xml(urlset)
    
    # Write to file
    with open('sitemap.xml', 'w', encoding='utf-8') as f:
        f.write(xml_string)
    
    print(f"Generated sitemap.xml with {len(urlset)} URLs")

def generate_rss_feed():
    """Generate RSS feed XML for blog posts"""
    rss = Element('rss')
    rss.set('version', '2.0')
    rss.set('xmlns:atom', 'http://www.w3.org/2005/Atom')
    
    channel = SubElement(rss, 'channel')
    
    # Channel information
    SubElement(channel, 'title').text = 'ASISA Construction Blog'
    SubElement(channel, 'link').text = BASE_URL
    SubElement(channel, 'description').text = 'Professional construction services blog covering BIM, Estimation, Document Control, Project Management, and more.'
    SubElement(channel, 'language').text = 'en-us'
    SubElement(channel, 'lastBuildDate').text = datetime.now().strftime('%a, %d %b %Y %H:%M:%S %z')
    SubElement(channel, 'generator').text = 'ASISA Blog Generator'
    
    # Atom self link
    atom_link = SubElement(channel, 'atom:link')
    atom_link.set('href', BASE_URL + 'rss.xml')
    atom_link.set('rel', 'self')
    atom_link.set('type', 'application/rss+xml')
    
    # Get all JSON files and sort by date (newest first)
    files = []
    for filename in os.listdir(POSTS_DIR):
        if filename.endswith('.json'):
            filepath = os.path.join(POSTS_DIR, filename)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    post_data = json.load(f)
                
                # Get date for sorting
                post_date = post_data.get('date', '2025-01-01')
                try:
                    date_obj = datetime.strptime(post_date, '%Y-%m-%d')
                except:
                    date_obj = datetime.fromtimestamp(os.path.getmtime(filepath))
                
                files.append((date_obj, filepath, post_data))
            except Exception as e:
                print(f"Error reading {filename}: {e}")
                continue
    
    # Sort by date, newest first
    files.sort(key=lambda x: x[0], reverse=True)
    
    # Limit to 50 most recent posts for RSS feed
    files = files[:50]
    
    for date_obj, filepath, post_data in files:
        item = SubElement(channel, 'item')
        
        title = post_data.get('title', 'Untitled')
        slug = post_data.get('slug', os.path.basename(filepath).replace('.json', ''))
        link = BASE_URL + slug + '/'
        description = post_data.get('excerpt', '')
        pub_date = date_obj.strftime('%a, %d %b %Y %H:%M:%S %z')
        
        # Format date for RSS (RFC 822)
        if not pub_date.endswith('+0000'):
            pub_date = pub_date.replace('+0000', '+00:00')
        
        SubElement(item, 'title').text = title
        SubElement(item, 'link').text = link
        guid = SubElement(item, 'guid')
        guid.text = link
        guid.set('isPermaLink', 'true')
        SubElement(item, 'description').text = description
        SubElement(item, 'pubDate').text = pub_date
        
        # Add categories as tags
        categories = post_data.get('categories', [])
        for category in categories:
            SubElement(item, 'category').text = category
        
        # Add image if available
        image_url = post_data.get('image', '')
        if image_url:
            enclosure = SubElement(item, 'enclosure')
            enclosure.set('url', image_url)
            enclosure.set('type', 'image/jpeg')
    
    # Generate XML string
    xml_string = prettify_xml(rss)
    
    # Write to file
    with open('rss.xml', 'w', encoding='utf-8') as f:
        f.write(xml_string)
    
    print(f"Generated rss.xml with {len(files)} posts")

def main():
    """Generate both XML files"""
    if not os.path.exists(POSTS_DIR):
        print(f"Error: {POSTS_DIR} directory not found!")
        return
    
    print("Generating XML files for blog posts...")
    print(f"Base URL: {BASE_URL}")
    print(f"Posts directory: {POSTS_DIR}\n")
    
    generate_sitemap()
    generate_rss_feed()
    
    print("\nXML files generated successfully!")
    print("- sitemap.xml (for search engines)")
    print("- rss.xml (RSS feed)")

if __name__ == "__main__":
    main()

