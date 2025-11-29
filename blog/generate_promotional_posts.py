#!/usr/bin/env python3
"""
Script to generate 500 promotional posts (201-700) with flow diagrams and graphical representations
"""
import json
import os
import random
from datetime import datetime, timedelta

# Service categories and topics
CATEGORIES = [
    "BIM", "Estimation", "Document Control", "Project Management",
    "Structural Steel", "Rebar", "Precast", "Plant Engineering",
    "Infrastructure", "Software Development"
]

# Promotional topics for each category
PROMOTIONAL_TOPICS = {
    "BIM": [
        "Professional BIM Modeling Services",
        "BIM Coordination and Clash Detection",
        "4D BIM Scheduling Solutions",
        "5D BIM Cost Estimation",
        "BIM Model Quality Assurance",
        "BIM Training and Implementation",
        "BIM Standards Compliance",
        "BIM Cloud Collaboration",
        "BIM for Facility Management",
        "BIM API Development"
    ],
    "Estimation": [
        "Accurate Cost Estimation Services",
        "Quantity Takeoff Automation",
        "Material Cost Analysis",
        "Labor Cost Estimation",
        "Equipment Cost Planning",
        "Cost Database Management",
        "Estimation Software Integration",
        "BIM-Based Estimation",
        "Change Order Estimation",
        "Estimation Quality Control"
    ],
    "Document Control": [
        "Document Management Systems",
        "Submittal Tracking Solutions",
        "RFI Management Services",
        "Version Control Systems",
        "Document Automation",
        "Quality Control Documentation",
        "Project Documentation Standards",
        "Cloud Document Management",
        "Document Review Processes",
        "Compliance Documentation"
    ],
    "Project Management": [
        "Construction Project Planning",
        "Schedule Management Services",
        "Resource Allocation Solutions",
        "Risk Management Strategies",
        "Progress Monitoring Systems",
        "Cost Control Services",
        "Quality Management",
        "Stakeholder Coordination",
        "Project Reporting Tools",
        "Construction Management Software"
    ],
    "Structural Steel": [
        "Steel Detailing Services",
        "Connection Design Solutions",
        "Fabrication Drawings",
        "Steel Shop Drawings",
        "Steel Coordination",
        "Steel Quality Assurance",
        "Steel Detailing Standards",
        "Tekla Structures Services",
        "Steel Estimation",
        "Steel Project Management"
    ],
    "Rebar": [
        "Rebar Detailing Services",
        "Rebar Shop Drawings",
        "Rebar Coordination",
        "Rebar Placement Optimization",
        "Rebar Quantity Takeoff",
        "Rebar Quality Control",
        "Rebar Standards Compliance",
        "Rebar BIM Modeling",
        "Rebar Fabrication Support",
        "Rebar Project Management"
    ],
    "Precast": [
        "Precast Detailing Services",
        "Precast Design Solutions",
        "Precast Shop Drawings",
        "Precast Coordination",
        "Precast Erection Planning",
        "Precast Quality Control",
        "Precast Standards Compliance",
        "Precast BIM Modeling",
        "Precast Manufacturing Support",
        "Precast Project Management"
    ],
    "Plant Engineering": [
        "Plant Design Services",
        "Piping Design Solutions",
        "Equipment Layout Design",
        "PID Development",
        "Plant Engineering Documentation",
        "Plant Coordination",
        "Plant Engineering Standards",
        "Plant Estimation Services",
        "Plant Project Management",
        "Plant Engineering Software"
    ],
    "Infrastructure": [
        "Infrastructure Design Services",
        "Road and Highway Design",
        "Bridge Design Solutions",
        "Utility Infrastructure Design",
        "Site Development Services",
        "Earthwork Design",
        "Infrastructure Documentation",
        "Civil 3D Services",
        "Infrastructure Estimation",
        "Infrastructure Project Management"
    ],
    "Software Development": [
        "Custom Construction Software",
        "BIM API Development",
        "Workflow Automation",
        "Data Integration Solutions",
        "Cloud Solutions Development",
        "Mobile App Development",
        "Construction Management Software",
        "Reporting Tools Development",
        "API Integration Services",
        "Software Customization"
    ]
}

# Unsplash image IDs for different categories
IMAGE_IDS = {
    "BIM": ["1581091226825-a6a2a5aee158", "1504307651254-35680f356dfd", "1552664730-d307ca884978"],
    "Estimation": ["1581091226825-a6a2a5aee158", "1552664730-d307ca884978", "1504307651254-35680f356dfd"],
    "Document Control": ["1581091226825-a6a2a5aee158", "1552664730-d307ca884978"],
    "Project Management": ["1504307651254-35680f356dfd", "1552664730-d307ca884978"],
    "Structural Steel": ["1581091226825-a6a2a5aee158", "1504307651254-35680f356dfd"],
    "Rebar": ["1581091226825-a6a2a5aee158", "1552664730-d307ca884978"],
    "Precast": ["1504307651254-35680f356dfd", "1581091226825-a6a2a5aee158"],
    "Plant Engineering": ["1552664730-d307ca884978", "1504307651254-35680f356dfd"],
    "Infrastructure": ["1581091226825-a6a2a5aee158", "1504307651254-35680f356dfd"],
    "Software Development": ["1552664730-d307ca884978", "1581091226825-a6a2a5aee158"]
}

def create_flow_diagram(title, steps):
    """Create an ASCII flow diagram"""
    diagram_html = f'''<div style="background: #f5f7fa; padding: 20px; border-left: 4px solid #0b61a4; margin: 25px 0; border-radius: 4px; overflow-x: auto;">
<h4 style="margin-top: 0; color: #0b61a4;">{title}</h4>
<div style="font-family: 'Courier New', monospace; line-height: 2; color: #333; text-align: center;">'''
    
    for i, step in enumerate(steps):
        step_lines = step.split('\n')
        max_width = max(len(line) for line in step_lines) if step_lines else 20
        
        diagram_html += '\n<div>┌' + '─' * (max_width + 2) + '┐</div>'
        for line in step_lines:
            padding = ' ' * ((max_width - len(line)) // 2)
            diagram_html += f'\n<div>│ {padding}{line}{" " * (max_width - len(line) - len(padding))} │</div>'
        diagram_html += '\n<div>└' + '─' * (max_width + 2) + '┘</div>'
        
        if i < len(steps) - 1:
            diagram_html += '\n<div>             │</div>'
            diagram_html += '\n<div>             ▼</div>'
    
    diagram_html += '\n</div>\n</div>'
    return diagram_html

def create_process_diagram(title, processes):
    """Create a process flow diagram"""
    diagram_html = f'''<div style="background: #f0f8ff; padding: 20px; border: 2px solid #0b61a4; margin: 25px 0; border-radius: 8px; overflow-x: auto;">
<h4 style="margin-top: 0; color: #0b61a4;">{title}</h4>
<div style="font-family: 'Courier New', monospace; line-height: 1.8; color: #333; text-align: center;">'''
    
    for i, process in enumerate(processes):
        process_lines = process.split('\n')
        max_width = max(len(line) for line in process_lines) if process_lines else 20
        
        diagram_html += '\n<div>╔' + '═' * (max_width + 2) + '╗</div>'
        for line in process_lines:
            padding = ' ' * ((max_width - len(line)) // 2)
            diagram_html += f'\n<div>║ {padding}{line}{" " * (max_width - len(line) - len(padding))} ║</div>'
        diagram_html += '\n<div>╚' + '═' * (max_width + 2) + '╝</div>'
        
        if i < len(processes) - 1:
            diagram_html += '\n<div>        ║</div>'
            diagram_html += '\n<div>        ║</div>'
            diagram_html += '\n<div>        ▼</div>'
    
    diagram_html += '\n</div>\n</div>'
    return diagram_html

def get_workflow_steps(category, topic):
    """Get workflow steps based on category and topic"""
    workflows = {
        "BIM": [
            "Project\nInitiation",
            "Model\nDevelopment",
            "Coordination\n& Review",
            "Quality\nAssurance",
            "Final\nDelivery"
        ],
        "Estimation": [
            "Project\nAnalysis",
            "Quantity\nTakeoff",
            "Cost\nCalculation",
            "Review\n& Validation",
            "Report\nDelivery"
        ],
        "Document Control": [
            "Document\nReceipt",
            "Review\n& Processing",
            "Approval\nWorkflow",
            "Distribution",
            "Archive\n& Tracking"
        ],
        "Project Management": [
            "Planning\nPhase",
            "Execution\nMonitoring",
            "Quality\nControl",
            "Progress\nReporting",
            "Project\nCompletion"
        ],
        "Structural Steel": [
            "Design\nReview",
            "Detailing\nProcess",
            "Coordination",
            "Quality\nCheck",
            "Drawing\nDelivery"
        ],
        "Rebar": [
            "Design\nAnalysis",
            "Rebar\nLayout",
            "Coordination",
            "Shop\nDrawings",
            "Final\nDelivery"
        ],
        "Precast": [
            "Design\nDevelopment",
            "Precast\nModeling",
            "Coordination",
            "Shop\nDrawings",
            "Manufacturing\nSupport"
        ],
        "Plant Engineering": [
            "Plant\nDesign",
            "Piping\nDesign",
            "Equipment\nLayout",
            "Documentation",
            "Project\nDelivery"
        ],
        "Infrastructure": [
            "Site\nAnalysis",
            "Design\nDevelopment",
            "Engineering\nCalculations",
            "Documentation",
            "Project\nDelivery"
        ],
        "Software Development": [
            "Requirements\nAnalysis",
            "Design\n& Development",
            "Testing\n& QA",
            "Integration",
            "Deployment\n& Support"
        ]
    }
    return workflows.get(category, [
        "Project\nInitiation",
        "Development\nPhase",
        "Quality\nAssurance",
        "Review\nProcess",
        "Final\nDelivery"
    ])

def generate_promotional_content(category, topic, post_num):
    """Generate promotional content with flow diagrams"""
    
    # Introduction
    intro = f"<h2>{topic}</h2>\n<p>ASISA provides professional {category.lower()} services that help construction companies deliver projects more efficiently and cost-effectively. Our experienced team specializes in {topic.lower()} and delivers high-quality results that meet international standards.</p>"
    
    # Service description
    service_desc = f"<h3>Our {category} Services</h3>\n<p>ASISA's {category.lower()} services combine technical expertise with practical construction knowledge. We understand the challenges construction companies face and provide solutions that address real project needs. Our offshore delivery model offers significant cost savings while maintaining the quality and standards your projects require.</p>"
    
    # Workflow diagram
    workflow_steps = get_workflow_steps(category, topic)
    workflow_diagram = create_flow_diagram(f"{category} Project Workflow", workflow_steps)
    
    # Benefits section
    benefits = f"<h3>Key Benefits of ASISA's {category} Services</h3>\n<p>Working with ASISA provides numerous advantages for your construction projects:</p>\n<ul>\n<li><strong>Cost-Effective Solutions:</strong> Our offshore delivery model provides access to skilled professionals at competitive rates</li>\n<li><strong>Quality Assurance:</strong> We follow international standards and best practices to ensure high-quality deliverables</li>\n<li><strong>Scalable Resources:</strong> Scale your team up or down based on project needs without long-term commitments</li>\n<li><strong>Expert Team:</strong> Our professionals have extensive experience with leading software platforms and industry standards</li>\n<li><strong>Timely Delivery:</strong> We understand project deadlines and deliver on time, every time</li>\n<li><strong>24/7 Support:</strong> Our global team structure enables continuous project progress</li>\n</ul>"
    
    # Process diagram
    process_steps = [
        "Initial\nConsultation",
        "Project\nPlanning",
        "Service\nDelivery",
        "Quality\nReview",
        "Client\nApproval"
    ]
    process_diagram = create_process_diagram("ASISA Service Delivery Process", process_steps)
    
    # Why choose ASISA
    why_asisa = f"<h3>Why Choose ASISA for {category} Services?</h3>\n<p>ASISA has established itself as a trusted partner for construction companies worldwide. Our {category.lower()} services are backed by years of experience working on diverse projects across different sectors. We understand the importance of accuracy, timeliness, and cost-effectiveness in construction projects.</p>\n\n<p>Our team stays current with the latest industry standards, software updates, and best practices. We invest in training and technology to ensure we can deliver the highest quality services. Whether you need ongoing support or project-specific assistance, ASISA provides flexible engagement models that fit your needs.</p>"
    
    # Services list
    services_list = f"<h3>Comprehensive {category} Solutions</h3>\n<p>ASISA offers a complete range of {category.lower()} services including:</p>\n<ul>\n<li>Professional {topic.lower()} with industry-standard tools</li>\n<li>Quality assurance and review processes</li>\n<li>Coordination with project teams</li>\n<li>Documentation and reporting</li>\n<li>Training and knowledge transfer</li>\n<li>Ongoing support and maintenance</li>\n</ul>"
    
    # Call to action
    cta = f"<h3>Get Started with ASISA</h3>\n<p>Ready to improve your construction project outcomes with professional {category.lower()} services? Contact ASISA today to discuss your project requirements. Our team will work with you to understand your needs and provide a customized solution that delivers value. We offer flexible engagement models, competitive pricing, and a commitment to quality that sets us apart.</p>\n\n<p>Partner with ASISA for {category.lower()} services that help you deliver projects on time, within budget, and to the highest quality standards. Our experienced team is ready to support your construction projects and help you achieve your goals.</p>"
    
    # Combine all content
    content = f'<div style="text-align: left; direction: ltr; max-width: 100%;">\n{intro}\n\n{service_desc}\n\n{workflow_diagram}\n\n{benefits}\n\n{process_diagram}\n\n{why_asisa}\n\n{services_list}\n\n{cta}\n</div>'
    
    return content

def generate_slug(title):
    """Generate URL-friendly slug from title"""
    slug = title.lower()
    slug = slug.replace("'", "")
    slug = slug.replace('"', '')
    slug = slug.replace(',', '')
    slug = slug.replace('.', '')
    slug = slug.replace(':', '')
    slug = slug.replace('(', '')
    slug = slug.replace(')', '')
    slug = slug.replace('&', 'and')
    slug = '-'.join(slug.split())
    slug = slug.replace('--', '-')
    return slug.strip('-')

def generate_post(post_num, category, topic):
    """Generate a single promotional post"""
    # Generate date (spread over time)
    base_date = datetime(2025, 1, 1)
    days_offset = (post_num - 201) * 2  # Spread posts over time
    post_date = (base_date + timedelta(days=days_offset)).strftime("%Y-%m-%d")
    
    # Generate title
    title = f"{topic} - Professional Services from ASISA"
    
    # Generate slug
    slug = generate_slug(topic)
    slug = f"{post_num:03d}-{slug}"
    
    # Get image
    image_id = random.choice(IMAGE_IDS.get(category, IMAGE_IDS["BIM"]))
    image_url = f"https://images.unsplash.com/photo-{image_id}?w=800&h=600&fit=crop"
    
    # Generate tags
    tags = [category.lower(), topic.lower().split()[0] if topic else "services"]
    tags.extend([word.lower() for word in topic.split()[:3] if len(word) > 3])
    tags = list(set(tags))[:5]  # Limit to 5 unique tags
    
    # Generate excerpt
    excerpt = f"{topic} — Professional services from ASISA. Expert solutions for construction projects. Contact us for quality offshore support and cost-effective services."
    
    # Generate content
    content = generate_promotional_content(category, topic, post_num)
    
    # Create post object
    post = {
        "title": title,
        "slug": slug,
        "date": post_date,
        "image": image_url,
        "categories": [category],
        "tags": tags,
        "excerpt": excerpt,
        "content": content
    }
    
    return post

def main():
    """Generate 500 promotional posts"""
    posts_dir = "posts"
    
    if not os.path.exists(posts_dir):
        os.makedirs(posts_dir)
        print(f"Created directory: {posts_dir}")
    
    # Generate posts 201-700
    post_num = 201
    total_posts = 500
    
    # Create list of all topics
    all_topics = []
    for category in CATEGORIES:
        for topic in PROMOTIONAL_TOPICS[category]:
            all_topics.append((category, topic))
    
    # Shuffle and repeat to get 500 posts
    random.seed(42)  # For reproducibility
    extended_topics = []
    while len(extended_topics) < total_posts:
        extended_topics.extend(all_topics)
    extended_topics = extended_topics[:total_posts]
    random.shuffle(extended_topics)
    
    print(f"Generating {total_posts} promotional posts (201-700)...")
    
    for i, (category, topic) in enumerate(extended_topics):
        current_post_num = 201 + i
        
        # Generate post
        post = generate_post(current_post_num, category, topic)
        
        # Save post
        filename = f"{current_post_num:03d}-{post['slug'].split('-', 1)[1] if '-' in post['slug'] else post['slug']}.json"
        filepath = os.path.join(posts_dir, filename)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(post, f, indent=4, ensure_ascii=False)
        
        if (i + 1) % 50 == 0:
            print(f"Generated {i + 1}/{total_posts} posts...")
    
    print(f"Successfully generated {total_posts} promotional posts!")
    print(f"Posts saved in: {posts_dir}")

if __name__ == "__main__":
    main()

