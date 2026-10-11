// 代码雨语料：Python / Go / Rust 的真实惯用写法（Nimbus 自写，MIT）。
// 每一列从一行代码里逐字落下，竖着读就是这行代码；关键字单独着色。
export const CORPUS = {
  python: [
    'def fib(n): return n if n < 2 else fib(n - 1) + fib(n - 2)',
    'squares = [x * x for x in range(10) if x % 2 == 0]',
    'async def fetch(session, url): async with session.get(url) as r: return await r.text()',
    "with open('post.md', encoding='utf-8') as f: lines = f.read().splitlines()",
    'from collections import Counter; top = Counter(words).most_common(3)',
    'def quicksort(a): return a if len(a) < 2 else quicksort([x for x in a[1:] if x < a[0]]) + [a[0]] + quicksort([x for x in a[1:] if x >= a[0]])',
    'class Node: def __init__(self, val, next=None): self.val, self.next = val, next',
    'import asyncio; asyncio.run(main())',
    'def walk(tree): yield tree.value; yield from (v for c in tree.children for v in walk(c))',
    "pairs = dict(zip(keys, values)); print(f'{len(pairs)} items')",
    'try: data = json.loads(raw) except ValueError as e: log.warning(e)',
    'sorted(posts, key=lambda p: p.created, reverse=True)[:10]',
    '@lru_cache(maxsize=None) def ways(n): return 1 if n <= 1 else ways(n - 1) + ways(n - 2)',
    'while left < right: mid = (left + right) // 2; left, right = (mid + 1, right) if a[mid] < x else (left, mid)',
  ],
  go: [
    'func main() { fmt.Println("hello, nimbus") }',
    'ch := make(chan int, 8)',
    'go func() { defer wg.Done(); results <- work(job) }()',
    'if err != nil { return nil, fmt.Errorf("read post: %w", err) }',
    'for i, v := range items { sum += v * i }',
    'type Post struct { ID int `json:"id"`; Title string `json:"title"` }',
    'select { case msg := <-inbox: handle(msg) case <-ctx.Done(): return ctx.Err() }',
    'http.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) { w.Write([]byte("ok")) })',
    'var mu sync.Mutex; mu.Lock(); defer mu.Unlock()',
    'ctx, cancel := context.WithTimeout(context.Background(), 3*time.Second); defer cancel()',
    'func Map[T, U any](xs []T, f func(T) U) []U { out := make([]U, 0, len(xs)); for _, x := range xs { out = append(out, f(x)) }; return out }',
    'rows, err := db.QueryContext(ctx, "SELECT id, title FROM posts WHERE status = ?", "publish")',
    'switch t := v.(type) { case string: return t case fmt.Stringer: return t.String() }',
  ],
  rust: [
    'fn main() { println!("hello, nimbus"); }',
    'let evens: Vec<i32> = (1..=10).filter(|x| x % 2 == 0).collect();',
    'impl fmt::Display for Post { fn fmt(&self, f: &mut fmt::Formatter) -> fmt::Result { write!(f, "{}", self.title) } }',
    'match res { Ok(body) => render(&body), Err(e) => eprintln!("error: {e}") }',
    'let mut words: HashMap<&str, usize> = HashMap::new();',
    'pub async fn list(State(db): State<Pool>) -> Json<Vec<Post>> { Json(db.posts().await) }',
    "fn longest<'a>(x: &'a str, y: &'a str) -> &'a str { if x.len() > y.len() { x } else { y } }",
    'let file = File::open("post.md")?;',
    '#[derive(Debug, Clone, Serialize, Deserialize)] struct Post { id: u64, title: String }',
    'thread::spawn(move || tx.send(compute()).unwrap());',
    'let total: u64 = posts.iter().map(|p| p.words).sum();',
    'if let Some(tag) = post.tags.first() { println!("#{tag}"); }',
    'enum Theme { Sky, Dusk, Cyber } impl Default for Theme { fn default() -> Self { Theme::Sky } }',
  ],
};

const KEYWORDS = {
  python: 'def return if else for in import from as with async await class lambda yield try except while and or not None True False self',
  go: 'func return if else for range go defer select case chan type struct var map make switch package import interface nil any',
  rust: 'fn let mut impl match pub async await move struct enum if else for in use Some None Ok Err Self self return where',
};

/** 把一行代码拆成 [{ch, kw}]，空格保留为间隔（不绘制）。 */
export function tokenize(line, lang) {
  const kws = new Set(KEYWORDS[lang].split(' '));
  const out = [];
  const re = /[A-Za-z_][A-Za-z0-9_]*|\s+|./g;
  let m;
  while ((m = re.exec(line))) {
    const t = m[0];
    const kw = kws.has(t);
    for (const ch of t) out.push({ ch: /\s/.test(ch) ? '' : ch, kw });
  }
  return out;
}

/** 按设置挑一行：mix 时三种语言随机。 */
export function pickLine(pref = 'mix') {
  const langs = pref === 'mix' || !CORPUS[pref] ? Object.keys(CORPUS) : [pref];
  const lang = langs[(Math.random() * langs.length) | 0];
  const lines = CORPUS[lang];
  return tokenize(lines[(Math.random() * lines.length) | 0], lang);
}
