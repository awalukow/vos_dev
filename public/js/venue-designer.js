(() => {
 const board=document.getElementById('venue-board'), json=document.getElementById('layout-json'), message=document.getElementById('editor-message'), props=document.getElementById('seat-properties');
 let seats=[],selected=-1,dividers=[],selection=new Set();
 const colors=['#713cce','#268b91','#bd7040','#b93e79','#4672b4'];
 const read=id=>document.getElementById(id).value;
 function error(text){message.textContent=text;}
 function validate(list,lines=dividers){
  if(!Array.isArray(list)||list.length>3000)throw Error('Use a seats array with at most 3,000 seats.');
  const labels=new Set(),positions=new Set();
  list.forEach(s=>{
   if(typeof s.label!=='string'||!s.label.trim()||s.label.length>40||typeof s.class!=='string'||!s.class.trim()||s.class.length>60)throw Error('Every seat needs a label and class.');
   if(!Number.isFinite(s.x)||!Number.isInteger(s.x*4)||!Number.isInteger(s.y)||s.x<0||s.x>100||s.y<0||s.y>100)throw Error('X must use quarter-seat increments and Y must be a whole number, from 0 to 100.');
   if(labels.has(s.label)||positions.has(s.x+':'+s.y))throw Error('Labels and seat positions must be unique.');
   labels.add(s.label);positions.add(s.x+':'+s.y);
  });
  if(!Array.isArray(lines)||lines.length>20)throw Error('Use at most 20 dividers.');
  const rows=new Set();
  lines.forEach(d=>{
   if(typeof d.label!=='string'||!d.label.trim()||d.label.length>60||!Number.isInteger(d.y)||d.y<0||d.y>100||rows.has(d.y))throw Error('Dividers need a label and unique row from 0 to 100.');
   if(list.some(s=>s.y===d.y))throw Error('Leave divider rows empty of seats.');
   rows.add(d.y);
  });
 }
 function draw(){
  board.replaceChildren();json.value=JSON.stringify({version:1,seats,dividers},null,2);
  const classes=[...new Set(seats.map(s=>s.class))];
  const width=Math.max(0,...seats.map(s=>s.x))*42+34;
  document.getElementById('venue-canvas').style.width=Math.max(450,width)+'px';
  board.style.width=width+'px';
  board.style.height=Math.max(200,(Math.max(0,...seats.map(s=>s.y),...dividers.map(d=>d.y))+1)*42)+'px';
  dividers.forEach(d=>{const line=document.createElement('div');line.className='venue-divider';line.style.top=d.y*42+'px';const label=document.createElement('span');label.textContent=d.label;line.append(label);board.append(line);});
  const choices=document.getElementById('venue-classes');choices.replaceChildren();
  classes.forEach(c=>{const o=document.createElement('option');o.value=c;choices.append(o);});
  const rowSelect=document.getElementById('select-row'),oldRow=rowSelect.value;rowSelect.replaceChildren();
  [...new Set(seats.map(s=>s.y))].sort((a,b)=>a-b).forEach(y=>{const row=seats.filter(s=>s.y===y);const o=document.createElement('option');o.value=y;o.textContent=row[0].label+' – '+row[row.length-1].label;rowSelect.append(o);});
  if([...rowSelect.options].some(o=>o.value===oldRow))rowSelect.value=oldRow;
  document.getElementById('selection-count').textContent=selection.size+' seats selected';
  seats.forEach((s,i)=>{
   const b=document.createElement('button');b.type='button';b.className='seat'+(selection.has(i)?' selected':'');b.textContent=s.label;b.setAttribute('aria-pressed',String(selection.has(i)));
   b.style.left=s.x*42+'px';b.style.top=s.y*42+'px';b.style.setProperty('--seat-color',colors[classes.indexOf(s.class)%colors.length]);
   b.title=s.label+' · '+s.class;b.setAttribute('aria-label',b.title);b.draggable=true;
   b.addEventListener('click',e=>select(i,document.getElementById('multi-select').checked||e.ctrlKey||e.metaKey));b.addEventListener('dragstart',e=>e.dataTransfer.setData('text/plain',String(i)));board.append(b);
  });
  document.getElementById('seat-count').textContent=seats.length+' seats · '+classes.join(' / ');
 }
 function select(i,multiple=false){
  if(!multiple)selection.clear();
  if(i>=0){if(multiple&&selection.has(i))selection.delete(i);else selection.add(i);}
  if(selection.size!==1)i=-1;else i=[...selection][0];
  selected=i;props.hidden=i<0;
  if(i>=0){const s=seats[i];['label','class','x','y'].forEach(k=>document.getElementById('seat-'+k).value=s[k]);}
  draw();
 }
 function load(raw){
  const data=JSON.parse(raw);if(data.version!==1)throw Error('Unsupported version. Use version 1.');
  validate(data.seats,data.dividers??[]);dividers=(data.dividers??[]).map(d=>({label:d.label,y:d.y}));seats=data.seats.map(s=>({label:s.label,class:s.class,x:s.x,y:s.y}));select(-1);error('Layout loaded. Save the venue to keep your changes.');
 }
 document.getElementById('add-row').addEventListener('click',()=>{
  try {
   const count=Number(read('row-count')),x=Number(read('row-x')),y=Number(read('row-y')),prefix=read('row-prefix').trim(),cls=read('row-class').trim();
   if(!Number.isInteger(count)||count<1||count>100||!prefix)throw Error('Enter a row label and 1–100 seats.');
   const next=[...seats,...Array.from({length:count},(_,i)=>({label:prefix+(i+1),class:cls,x:x+i,y}))];validate(next);seats=next;
   document.getElementById('row-y').value=Math.min(100,y+1);
   if(/^[A-Y]$/.test(prefix))document.getElementById('row-prefix').value=String.fromCharCode(prefix.charCodeAt(0)+1);
   select(-1);error('Row added. Drag seats to leave an aisle, or click a seat to edit it.');
  }catch(e){error(e.message);}
 });
 document.getElementById('apply-seat').addEventListener('click',()=>{
  if(selected<0)return;
  try{const next=seats.map(s=>({...s}));next[selected]={label:read('seat-label').trim(),class:read('seat-class').trim(),x:Number(read('seat-x')),y:Number(read('seat-y'))};validate(next);seats=next;draw();error('Seat updated.');}catch(e){error(e.message);}
 });
 document.getElementById('remove-seat').addEventListener('click',()=>{if(selected<0)return;seats.splice(selected,1);select(-1);});
 function selectGroup(indices){selection=new Set(indices);selected=-1;props.hidden=true;draw();}
 document.getElementById('select-row-seats').addEventListener('click',()=>selectGroup(seats.map((s,i)=>s.y===Number(read('select-row'))?i:-1).filter(i=>i>=0)));
 document.getElementById('select-all-seats').addEventListener('click',()=>selectGroup(seats.map((s,i)=>i)));
 document.getElementById('clear-selection').addEventListener('click',()=>select(-1));
 document.getElementById('apply-class').addEventListener('click',()=>{
  try{if(!selection.size)throw Error('Select seats first.');const cls=read('bulk-class').trim(),next=seats.map((s,i)=>selection.has(i)?{...s,class:cls}:s);validate(next);seats=next;if(selected>=0)document.getElementById('seat-class').value=cls;draw();error('Updated class for '+selection.size+' seats. Save the venue to keep your changes.');}catch(e){error(e.message);}
 });
 ['seat-class','row-class'].forEach(id=>document.getElementById(id).setAttribute('list','venue-classes'));
 board.addEventListener('dragover',e=>e.preventDefault());
 board.addEventListener('drop',e=>{
  e.preventDefault();const i=Number(e.dataTransfer.getData('text/plain'));
  if(!Number.isInteger(i)||!seats[i])return;
  const rect=board.getBoundingClientRect(),next=seats.map(s=>({...s}));
  next[i].x=Math.round((e.clientX-rect.left-17)/42*4)/4;next[i].y=Math.round((e.clientY-rect.top-16)/42);
  try{validate(next);seats=next;select(i);error('Seat moved.');}catch(err){error(err.message);}
 });
 document.getElementById('layout-file').addEventListener('change',async e=>{
  const f=e.target.files[0];if(!f)return;
  if(f.size>1048576){error('JSON files must be smaller than 1 MB.');e.target.value='';return;}
  try{load(await f.text());e.target.value='';}catch(err){error('Import failed: '+err.message);e.target.value='';}
 });
 document.getElementById('apply-json').addEventListener('click',()=>{try{load(json.value);}catch(e){error(e.message);}});
 document.getElementById('venue-form').addEventListener('submit',e=>{
  try{load(json.value);if(!seats.length)throw Error('Add at least one seat before saving.');}catch(err){e.preventDefault();error(err.message);}
 });
 try{load(json.value);}catch(e){error(e.message);draw();}
})();
