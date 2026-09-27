const escapeHtml = (s: string) =>
  s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')

/** Renders the bot's legacy Markdown (*bold* _italic_ `code` [text](url)) as safe HTML for previews. */
export function renderBotMarkdown(markdown: string): string {
  let html = escapeHtml(markdown)
  html = html.replace(/`([^`\n]+)`/g, '<code class="rounded bg-slate-100 px-1 font-mono text-xs">$1</code>')
  html = html.replace(/\[([^\]\n]+)\]\(([^)\s]+)\)/g, (_m, text: string, url: string) =>
    /^https?:\/\//i.test(url) ? `<a href="${url}" target="_blank" rel="noopener" class="text-sky-600 underline">${text}</a>` : text
  )
  html = html.replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>')
  html = html.replace(/(^|[\s(])_([^_\n]+)_(?=[\s).,!?:؛،]|$)/gm, '$1<em>$2</em>')
  return html
}
