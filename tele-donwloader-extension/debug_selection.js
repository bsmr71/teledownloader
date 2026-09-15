


































































































// Debug script - inject into Telegram page to discover selection DOM structure
// Run this in browser console while messages are selected

(function() {
  // 1. Find ALL elements with "selected" or "is-selected" anywhere in their class
  const allSelected = document.querySelectorAll('[class*="selected"], [class*="Selected"]');
  
  console.log('=== ALL ELEMENTS WITH "selected" IN CLASS ===');
  console.log(`Total found: ${allSelected.length}`);
  
  allSelected.forEach((el, i) => {
    const tag = el.tagName;
    const classes = el.className;
    const id = el.id || '';
    const dataMid = el.getAttribute('data-mid') || '';
    const dataMessageId = el.getAttribute('data-message-id') || '';
    const parent = el.parentElement;
    const parentClasses = parent ? parent.className : '';
    
    console.log(`[${i}] <${tag}> class="${classes}" id="${id}" data-mid="${dataMid}" data-message-id="${dataMessageId}"`);
    console.log(`    parent: <${parent?.tagName}> class="${parentClasses}"`);
    
    // Check for media inside
    const imgs = el.querySelectorAll('img');
    const videos = el.querySelectorAll('video');
    console.log(`    contains: ${imgs.length} imgs, ${videos.length} videos`);
  });

  // 2. Also check for bubbles
  const bubbles = document.querySelectorAll('.bubble');
  const selectedBubbles = Array.from(bubbles).filter(b => 
    b.classList.contains('is-selected') || b.classList.contains('selected')
  );
  console.log('\n=== BUBBLES WITH SELECTED ===');
  console.log(`Total bubbles: ${bubbles.length}, Selected: ${selectedBubbles.length}`);
  selectedBubbles.forEach((b, i) => {
    const mid = b.getAttribute('data-mid') || b.dataset.mid || '';
    console.log(`[${i}] bubble data-mid="${mid}" classes="${b.className}"`);
  });
  
  // 3. Check bottom toolbar text
  const toolbar = document.querySelector('[class*="select"], [class*="Select"]');
  if (toolbar) {
    console.log('\n=== SELECTION TOOLBAR ===');
    console.log(`text: "${toolbar.textContent}"`);
    console.log(`class: "${toolbar.className}"`);
  }
})();
