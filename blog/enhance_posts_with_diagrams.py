#!/usr/bin/env python3
"""
Script to enhance blog posts with additional paragraphs and flow diagrams
"""
import json
import os
import re

def create_workflow_diagram(title, steps):
    """Create an ASCII workflow diagram"""
    diagram_html = f'''<div style="background: #f5f7fa; padding: 20px; border-left: 4px solid #0b61a4; margin: 25px 0; border-radius: 4px; overflow-x: auto;">
<h4 style="margin-top: 0; color: #0b61a4;">{title}</h4>
<div style="font-family: 'Courier New', monospace; line-height: 2; color: #333; text-align: center;">'''
    
    for i, step in enumerate(steps):
        step_lines = step.split('\n')
        max_width = max(len(line) for line in step_lines)
        
        diagram_html += '\n<div>┌' + '─' * (max_width + 2) + '┐</div>'
        for line in step_lines:
            padding = ' ' * ((max_width - len(line)) // 2)
            diagram_html += f'\n<div>│ {padding}{line}{padding} │</div>'
        diagram_html += '\n<div>└' + '─' * (max_width + 2) + '┘</div>'
        
        if i < len(steps) - 1:
            diagram_html += '\n<div>             │</div>'
            diagram_html += '\n<div>             ▼</div>'
    
    diagram_html += '\n</div>\n</div>'
    return diagram_html

def enhance_post_content(content):
    """Add paragraphs and flow diagrams to post content"""
    # This is a template - actual enhancement would parse and add content strategically
    enhanced = content
    
    # Add workflow diagram after "Essential BIM Workflows" section if it exists
    if "Essential BIM Workflows" in content and "BIM Project Workflow Diagram" not in content:
        workflow_diagram = create_workflow_diagram(
            "BIM Project Workflow Diagram",
            [
                "Model Creation\n(Arch/Struct/MEP)",
                "Model Federation\n& Coordination",
                "Clash Detection\n& Resolution",
                "Documentation\nGeneration",
                "Construction\n& Facility Mgmt"
            ]
        )
        
        # Insert diagram after the workflow section intro paragraph
        pattern = r'(<h3>Essential BIM Workflows</h3>.*?<p>.*?</p>)'
        match = re.search(pattern, content, re.DOTALL)
        if match:
            insert_pos = match.end()
            enhanced = content[:insert_pos] + '\n\n' + workflow_diagram + '\n\n' + content[insert_pos:]
    
    return enhanced

# Example usage
if __name__ == "__main__":
    posts_dir = "posts"
    
    # Process first post as example
    post_file = os.path.join(posts_dir, "001-bim-fundamentals-for-beginners.json")
    
    if os.path.exists(post_file):
        with open(post_file, 'r', encoding='utf-8') as f:
            post_data = json.load(f)
        
        # Enhance content
        original_content = post_data['content']
        enhanced_content = enhance_post_content(original_content)
        
        # Update post
        post_data['content'] = enhanced_content
        
        # Write back
        with open(post_file, 'w', encoding='utf-8') as f:
            json.dump(post_data, f, indent=4, ensure_ascii=False)
        
        print(f"Enhanced {post_file}")

