import json
import os
import re
import random

# Professional headings and subtitles templates
heading_templates = {
    "BIM": [
        ("What is Building Information Modeling?", "Understanding the fundamentals of BIM technology"),
        ("Benefits of BIM Implementation", "How BIM transforms construction projects"),
        ("BIM Software and Tools", "Essential software for BIM workflows"),
        ("BIM Best Practices", "Proven strategies for successful BIM projects"),
        ("Why Choose ASISA for BIM Services?", "Professional offshore BIM support"),
    ],
    "Estimation": [
        ("Accurate Cost Estimation Methods", "Professional estimation techniques"),
        ("Estimation Software and Tools", "Technology for precise cost estimates"),
        ("Quantity Takeoff Best Practices", "Ensuring accurate material calculations"),
        ("Cost Control Strategies", "Managing project budgets effectively"),
        ("ASISA Estimation Services", "Expert cost estimation support"),
    ],
    "Document Control": [
        ("Document Management Essentials", "Organizing project documentation"),
        ("Version Control Best Practices", "Maintaining document accuracy"),
        ("Submittal Management Process", "Streamlining approval workflows"),
        ("Document Control Software", "Tools for efficient document management"),
        ("Professional Document Control Services", "ASISA's expert document management"),
    ],
    "Project Management": [
        ("Project Planning Strategies", "Effective construction project planning"),
        ("Schedule Management", "Keeping projects on track"),
        ("Resource Allocation", "Optimizing project resources"),
        ("Risk Management", "Identifying and mitigating project risks"),
        ("ASISA Project Management Services", "Professional project oversight"),
    ],
    "Structural Steel": [
        ("Steel Detailing Fundamentals", "Creating accurate shop drawings"),
        ("Connection Design", "Designing efficient steel connections"),
        ("Fabrication Support", "Supporting steel fabrication processes"),
        ("Quality Assurance", "Ensuring steel detailing accuracy"),
        ("Professional Steel Detailing Services", "ASISA's expert steel services"),
    ],
    "Rebar": [
        ("Rebar Detailing Best Practices", "Creating precise rebar layouts"),
        ("Coordination Strategies", "Avoiding rebar conflicts"),
        ("Shop Drawing Creation", "Detailed rebar documentation"),
        ("Quality Control", "Ensuring rebar accuracy"),
        ("Expert Rebar Services", "ASISA's professional rebar support"),
    ],
    "Precast": [
        ("Precast Design Principles", "Designing efficient precast systems"),
        ("Connection Design", "Designing precast connections"),
        ("Manufacturing Support", "Supporting precast production"),
        ("Erection Planning", "Planning precast installation"),
        ("Professional Precast Services", "ASISA's precast expertise"),
    ],
    "Plant Engineering": [
        ("Plant Design Fundamentals", "Designing industrial facilities"),
        ("Piping Design", "Creating efficient piping systems"),
        ("Equipment Layout", "Optimizing plant layouts"),
        ("Documentation Standards", "Maintaining plant documentation"),
        ("Expert Plant Engineering Services", "ASISA's plant engineering support"),
    ],
    "Infrastructure": [
        ("Infrastructure Design Principles", "Designing public infrastructure"),
        ("Road and Highway Design", "Creating transportation infrastructure"),
        ("Bridge Design", "Designing bridge structures"),
        ("Utility Systems", "Designing utility infrastructure"),
        ("Professional Infrastructure Services", "ASISA's infrastructure expertise"),
    ],
    "Software Development": [
        ("Custom Software Solutions", "Developing construction software"),
        ("BIM API Development", "Extending BIM capabilities"),
        ("Workflow Automation", "Automating construction processes"),
        ("Integration Services", "Connecting construction systems"),
        ("Expert Software Development", "ASISA's software solutions"),
    ],
}

def get_headings_for_category(categories):
    """Get appropriate headings based on category"""
    for cat in categories:
        if cat in heading_templates:
            return random.choice(heading_templates[cat])
    return ("Key Benefits", "Understanding the advantages"), ("Implementation", "How to get started")

def enhance_content(content, categories, title):
    """Enhance content with professional headings and formatting"""
    # Remove existing HTML tags
    clean_content = re.sub(r'<[^>]+>', '', content)
    sentences = re.split(r'[.!?]', clean_content)
    sentences = [s.strip() for s in sentences if s.strip()]
    
    # Get headings
    headings = get_headings_for_category(categories)
    
    # Structure content with headings
    enhanced = f"<h2>{headings[0]}</h2>\n<p>{sentences[0] if sentences else ''}.</p>\n"
    
    if len(sentences) > 1:
        enhanced += f"<h3>{headings[1]}</h3>\n<p>{'. '.join(sentences[1:4]) if len(sentences) > 3 else '. '.join(sentences[1:])}.</p>\n"
    
    if len(sentences) > 4:
        enhanced += f"<h3>Why Choose ASISA?</h3>\n<p>{'. '.join(sentences[4:7]) if len(sentences) > 6 else '. '.join(sentences[4:])}.</p>\n"
    
    if len(sentences) > 7:
        enhanced += f"<h3>Our Services</h3>\n<p>{'. '.join(sentences[7:])}.</p>\n"
    
    # Ensure approximately 200 words
    word_count = len(re.sub(r'<[^>]+>', '', enhanced).split())
    if word_count < 180:
        # Add more content
        additional = " Contact ASISA today to learn how our professional services can benefit your construction projects. We provide comprehensive support, training, and ongoing assistance."
        enhanced += f"<p>{additional}</p>\n"
    
    return enhanced.strip()

def enhance_all_posts():
    """Enhance all existing posts"""
    post_files = [f for f in os.listdir("posts") if f.endswith('.json')]
    
    enhanced = 0
    for filename in sorted(post_files):
        filepath = os.path.join("posts", filename)
        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                post = json.load(f)
            
            # Enhance content
            original_content = post.get('content', '')
            categories = post.get('categories', [])
            title = post.get('title', '')
            
            enhanced_content = enhance_content(original_content, categories, title)
            post['content'] = enhanced_content
            
            # Write back
            with open(filepath, 'w', encoding='utf-8') as f:
                json.dump(post, f, indent=4, ensure_ascii=False)
            
            enhanced += 1
            if enhanced % 50 == 0:
                print(f"Progress: {enhanced}/{len(post_files)} posts enhanced...")
        except Exception as e:
            print(f"Error enhancing {filename}: {e}")
    
    print(f"\nEnhanced {enhanced} posts with professional headings and formatting")

if __name__ == "__main__":
    enhance_all_posts()

