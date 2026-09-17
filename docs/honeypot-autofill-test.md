---
title: Honeypot autofill test
nav_order: 8
description: "Free tool: test whether your browser or password manager fills hidden honeypot fields, and check your own form HTML for bait fields that autofill will fill in."
image: /assets/images/visitor-vs-bot.png
---

<style>
  .hpt-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
  @media (min-width: 50rem) { .hpt-grid { grid-template-columns: 1fr 1fr; } }
  .hpt-card { border: 1px solid #e6e1e8; border-radius: 8px; padding: 1rem 1.25rem; background: #fff; }
  .hpt-card h3 { margin-top: 0; }
  .hpt-form label { display: block; font-weight: 600; margin: .75rem 0 .25rem; }
  .hpt-form input, .hpt-form textarea, .hpt-textarea {
    width: 100%; box-sizing: border-box; padding: .5rem .6rem; border: 1px solid #c5c0cc; border-radius: 6px; font: inherit;
  }
  .hpt-textarea { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8rem; min-height: 14rem; }
  .hpt-bait-sr { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip-path: inset(50%) !important; white-space: nowrap !important; border: 0 !important; }
  .hpt-bait-offscreen { position: absolute !important; left: -10000px !important; top: auto !important; width: 1px !important; height: 1px !important; overflow: hidden !important; }
  .hpt-bait-none { display: none !important; }
  .hpt-reveal .hpt-bait { position: static !important; width: auto !important; height: auto !important; margin: .5rem 0 !important; overflow: visible !important; clip-path: none !important; display: block !important; white-space: normal !important; padding: .5rem !important; border: 2px dashed #f59e0b !important; border-radius: 6px; background: #fffbeb; }
  .hpt-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
  .hpt-table th, .hpt-table td { text-align: left; padding: .45rem .5rem; border-bottom: 1px solid #eeebee; vertical-align: top; }
  .hpt-badge { display: inline-block; padding: .1rem .5rem; border-radius: 999px; font-size: .78rem; font-weight: 700; white-space: nowrap; }
  .hpt-ok { background: #dcfce7; color: #166534; }
  .hpt-bad { background: #fee2e2; color: #991b1b; }
  .hpt-warn { background: #fef3c7; color: #92400e; }
  .hpt-muted { color: #6b7280; font-size: .85rem; }
  .hpt-controls { display: flex; flex-wrap: wrap; gap: .5rem 1rem; align-items: center; margin: .75rem 0; }
  .hpt-controls select { padding: .3rem; }
  .hpt-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .82rem; }
  .hpt-list { margin: .25rem 0 0; padding-left: 1.1rem; }
  .hpt-scroll { overflow-x: auto; }
</style>

# Honeypot autofill test

A honeypot field that browser autofill or a password manager fills in turns a real visitor into "spam". This page lets you see it happen in your own browser, and check your own form.

Nothing on this page is sent anywhere. The form has no action, and everything runs in your browser.
{: .hpt-muted }

## 1. Test your browser and password manager

Fill in the form below with autofill: click the name or email field and pick a saved profile, or use your password manager's "fill identity". Then look at the table: every hidden bait field that got a value would have blocked you.

<div class="hpt-grid">
  <div class="hpt-card">
    <h3>Contact form</h3>
    <div class="hpt-controls">
      <label class="hpt-muted" for="hpt-hide">Hide bait fields with</label>
      <select id="hpt-hide">
        <option value="hpt-bait-sr">screen-reader-only CSS (clip)</option>
        <option value="hpt-bait-offscreen">off-screen (left: -10000px)</option>
        <option value="hpt-bait-none">display: none</option>
      </select>
      <label class="hpt-muted"><input type="checkbox" id="hpt-reveal"> Show bait fields</label>
    </div>
    <form id="hpt-form" class="hpt-form" autocomplete="on" novalidate>
      <label for="hpt-name">Name</label>
      <input id="hpt-name" name="name" type="text" autocomplete="name">

      <label for="hpt-email">Email</label>
      <input id="hpt-email" name="email" type="email" autocomplete="email">

      <label for="hpt-phone">Phone</label>
      <input id="hpt-phone" name="phone" type="tel" autocomplete="tel">

      <div id="hpt-baits"></div>

      <label for="hpt-message">Message</label>
      <textarea id="hpt-message" name="message" rows="3"></textarea>

      <p class="hpt-controls">
        <button type="submit" class="btn btn-primary">Send (nothing is sent)</button>
        <button type="button" id="hpt-reset" class="btn">Clear</button>
      </p>
    </form>
  </div>

  <div class="hpt-card" aria-live="polite">
    <h3>Result</h3>
    <p id="hpt-summary" class="hpt-muted">Waiting for autofill…</p>
    <table class="hpt-table">
      <thead><tr><th>Hidden bait field</th><th>Status</th></tr></thead>
      <tbody id="hpt-results"></tbody>
    </table>
    <p class="hpt-muted">Browsers only fill fields they recognise, and some skip fields that are not visible. Try each hiding method: results differ per browser and password manager.</p>
  </div>
</div>

## 2. Check your own form

Paste the HTML of a form, for example from your browser's "View source" or "Copy outer HTML". The checker finds fields that look like a honeypot and tells you whether autofill is likely to fill them in.

<div class="hpt-card">
  <label for="hpt-html" class="hpt-muted">Form HTML</label>
  <textarea id="hpt-html" class="hpt-textarea" spellcheck="false" placeholder="<form>…</form>"></textarea>
  <p class="hpt-controls">
    <button type="button" id="hpt-check" class="btn btn-primary">Check form</button>
    <button type="button" id="hpt-example" class="btn">Load an example</button>
  </p>
  <div id="hpt-check-results" aria-live="polite"></div>
</div>

The checker compares names, ids, labels and placeholders with words that browser autofill and password managers commonly recognise. It can't know every rule of every browser, so treat a clean result as "probably fine" and confirm it with the test above.
{: .hpt-muted }

## What to do about a risky field

- Give it a name and label that no autofill rule matches, such as `referral_3f9a` and "Leave this field empty".
- Add `autocomplete="off"`, `data-1p-ignore`, `data-lpignore="true"`, `data-bwignore` and `data-form-type="other"`.
- Show a visible error when a submission is blocked, instead of a blank page or a fake "thank you".

Read [why honeypots block real visitors](autofill-blocks-real-visitors.md), or use [darvis/livewire-honeypot](index.md), which does all of this for Livewire and Laravel forms.

<script>
(function () {
  'use strict';

  /* Bait fields as they appear in common honeypot setups, plus this package's own. */
  var BAITS = [
    { name: 'website', label: 'Website', note: 'Classic honeypot name' },
    { name: 'url', label: 'URL', note: 'Classic honeypot name' },
    { name: 'company', label: 'Company', note: 'Classic honeypot name' },
    { name: 'email_confirm', label: 'Confirm email', note: 'Looks like a real field' },
    { name: 'hp_website', label: 'Website (leave empty)', note: 'Label still says "Website"' },
    { name: 'my_name_x7k2', label: 'Name', note: 'Random suffix, but contains "name"' },
    { name: 'referral_3f9a', label: 'Leave this field empty', note: 'darvis/livewire-honeypot', safe: true }
  ];

  var baitBox = document.getElementById('hpt-baits');
  var results = document.getElementById('hpt-results');
  var summary = document.getElementById('hpt-summary');
  var hideSelect = document.getElementById('hpt-hide');
  var form = document.getElementById('hpt-form');

  function el(tag, attrs, text) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (key) { node.setAttribute(key, attrs[key]); });
    if (text !== undefined) { node.textContent = text; }
    return node;
  }

  function renderBaits() {
    baitBox.innerHTML = '';
    BAITS.forEach(function (bait, index) {
      var wrap = el('div', { 'class': 'hpt-bait ' + hideSelect.value, 'aria-hidden': 'true' });
      var id = 'hpt-bait-' + index;
      var label = el('label', { 'for': id }, bait.label);
      var attrs = { id: id, name: bait.name, type: 'text', tabindex: '-1' };
      if (bait.safe) {
        attrs.autocomplete = 'off';
        attrs['data-1p-ignore'] = '';
        attrs['data-lpignore'] = 'true';
        attrs['data-bwignore'] = '';
        attrs['data-form-type'] = 'other';
      }
      wrap.appendChild(label);
      wrap.appendChild(el('input', attrs));
      baitBox.appendChild(wrap);
    });
    update();
  }

  function update() {
    var filled = 0;
    results.innerHTML = '';
    BAITS.forEach(function (bait, index) {
      var input = document.getElementById('hpt-bait-' + index);
      var value = input ? input.value : '';
      if (value !== '') { filled++; }

      var row = el('tr');
      var nameCell = el('td');
      nameCell.appendChild(el('span', { 'class': 'hpt-code' }, 'name="' + bait.name + '"'));
      nameCell.appendChild(el('div', { 'class': 'hpt-muted' }, 'Label "' + bait.label + '" · ' + bait.note));
      var statusCell = el('td');
      statusCell.appendChild(value === ''
        ? el('span', { 'class': 'hpt-badge hpt-ok' }, 'Empty')
        : el('span', { 'class': 'hpt-badge hpt-bad' }, 'Filled: would block you'));
      row.appendChild(nameCell);
      row.appendChild(statusCell);
      results.appendChild(row);
    });

    var anyReal = ['hpt-name', 'hpt-email', 'hpt-phone'].some(function (id) {
      return document.getElementById(id).value !== '';
    });
    if (filled > 0) {
      summary.textContent = filled + ' hidden field' + (filled === 1 ? ' was' : 's were') + ' filled. A honeypot using ' + (filled === 1 ? 'that name' : 'those names') + ' would reject you as spam.';
    } else if (anyReal) {
      summary.textContent = 'No hidden field was filled. Try autofill again with another hiding method, or another password manager.';
    } else {
      summary.textContent = 'Waiting for autofill…';
    }
  }

  hideSelect.addEventListener('change', renderBaits);
  document.getElementById('hpt-reveal').addEventListener('change', function (event) {
    form.classList.toggle('hpt-reveal', event.target.checked);
  });
  document.getElementById('hpt-reset').addEventListener('click', function () {
    form.reset();
    update();
  });
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    update();
  });
  form.addEventListener('input', update);
  form.addEventListener('change', update);
  /* Some browsers autofill without firing input events. */
  window.setInterval(update, 700);

  renderBaits();

  /* ---- Checker ---- */

  /* Words browser autofill and password managers commonly match, as whole words or parts of names. */
  var AUTOFILL_PATTERNS = [
    { re: /e-?mail|courriel/, what: 'email' },
    { re: /(^|[^a-z])(first|last|full|given|family|sur|nick|user|your|contact)?_?name([^a-z]|$)|fname|lname|surname|nickname/, what: 'name' },
    { re: /phone|mobile|(^|[^a-z])tel([^a-z]|$)|telefoon|telephone|fax/, what: 'phone' },
    { re: /address|street|addr|(^|[^a-z])city([^a-z]|$)|town|(^|[^a-z])zip|postal|postcode|country|province|region|(^|[^a-z])state([^a-z]|$)/, what: 'address' },
    { re: /company|organi[sz]ation|business|(^|[^a-z])org([^a-z]|$)|bedrijf|firma/, what: 'company' },
    { re: /website|web-?site|homepage|home-?page|(^|[^a-z])url([^a-z]|$)|(^|[^a-z])web([^a-z]|$)/, what: 'website' },
    { re: /user-?name|login|password|passwd/, what: 'login' },
    { re: /birth|(^|[^a-z])dob([^a-z]|$)|card|(^|[^a-z])cc-|cvc|cvv|iban/, what: 'personal or payment data' }
  ];
  var RECOGNISABLE = /honey|(^|[^a-z])hp([^a-z]|$)|hp_|bot|trap|spam|(^|[^a-z])fake/;
  var IGNORE_ATTRS = ['data-1p-ignore', 'data-lpignore', 'data-bwignore', 'data-form-type'];

  function normalise(value) {
    return String(value || '')
      .replace(/([a-z])([A-Z])/g, '$1_$2')
      .toLowerCase();
  }

  function styleHides(style) {
    style = normalise(style).replace(/\s+/g, '');
    return /display:none|visibility:hidden|left:-\d{3,}|top:-\d{3,}|clip-path:inset\(50%\)|clip:rect\(0|opacity:0([^.]|$)|height:0|width:0|height:1px|width:1px/.test(style);
  }

  function isHidden(input) {
    for (var node = input; node && node.nodeType === 1; node = node.parentElement) {
      if (node.hasAttribute('hidden')) { return 'hidden attribute'; }
      if (node.getAttribute('aria-hidden') === 'true') { return 'aria-hidden'; }
      if (styleHides(node.getAttribute('style'))) { return 'inline style'; }
      var cls = normalise(node.getAttribute('class'));
      if (/(^|\s)(hidden|d-none|sr-only|visually-hidden|invisible)(\s|$)/.test(cls) || RECOGNISABLE.test(cls)) { return 'class "' + node.getAttribute('class') + '"'; }
    }
    if (input.getAttribute('tabindex') === '-1') { return 'tabindex="-1"'; }
    return null;
  }

  function labelFor(doc, input) {
    var texts = [];
    if (input.id) {
      doc.querySelectorAll('label').forEach(function (label) {
        if (label.getAttribute('for') === input.id) { texts.push(label.textContent); }
      });
    }
    var parentLabel = input.closest('label');
    if (parentLabel) { texts.push(parentLabel.textContent); }
    return texts.join(' ').replace(/\s+/g, ' ').trim();
  }

  function checkForm(html) {
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var inputs = Array.prototype.filter.call(doc.querySelectorAll('input, textarea'), function (input) {
      var type = (input.getAttribute('type') || 'text').toLowerCase();
      return input.tagName === 'TEXTAREA' || ['text', 'email', 'url', 'tel', 'search', ''].indexOf(type) !== -1;
    });

    var candidates = [];
    inputs.forEach(function (input) {
      var hiddenBy = isHidden(input);
      var identity = [input.getAttribute('name'), input.id, input.getAttribute('class')].map(normalise).join(' ');
      if (hiddenBy || RECOGNISABLE.test(identity)) {
        candidates.push({ input: input, hiddenBy: hiddenBy || 'name looks like a honeypot' });
      }
    });

    return { total: inputs.length, candidates: candidates.map(function (candidate) {
      return assess(doc, candidate.input, candidate.hiddenBy);
    }) };
  }

  function assess(doc, input, hiddenBy) {
    var name = input.getAttribute('name') || '';
    var label = labelFor(doc, input);
    var sources = {
      name: normalise(name),
      id: normalise(input.id),
      label: normalise(label),
      placeholder: normalise(input.getAttribute('placeholder')),
      'aria-label': normalise(input.getAttribute('aria-label'))
    };
    var problems = [];
    var warnings = [];
    var autocomplete = normalise(input.getAttribute('autocomplete'));

    AUTOFILL_PATTERNS.forEach(function (pattern) {
      var matched = Object.keys(sources).filter(function (source) {
        return sources[source] && pattern.re.test(sources[source]);
      });
      if (matched.length) {
        problems.push('The ' + matched.join(', ') + ' look' + (matched.length === 1 ? 's' : '') + ' like a ' + pattern.what + ' field, which autofill may fill in.');
      }
    });
    if (autocomplete && autocomplete !== 'off' && autocomplete !== 'new-password') {
      problems.push('autocomplete="' + input.getAttribute('autocomplete') + '" invites the browser to fill it in.');
    }
    if (autocomplete !== 'off') {
      warnings.push('Add autocomplete="off".');
    }
    var missingIgnore = IGNORE_ATTRS.filter(function (attr) { return !input.hasAttribute(attr); });
    if (missingIgnore.length) {
      warnings.push('Password managers are not told to skip it (missing ' + missingIgnore.join(', ') + ').');
    }
    if (RECOGNISABLE.test(sources.name + ' ' + sources.id)) {
      warnings.push('The name "' + name + '" is easy for a bot to recognise and skip.');
    }

    return {
      name: name || '(no name)',
      label: label,
      hiddenBy: hiddenBy,
      problems: problems,
      warnings: warnings
    };
  }

  function renderCheck(result) {
    var box = document.getElementById('hpt-check-results');
    box.innerHTML = '';
    if (result.total === 0) {
      box.appendChild(el('p', { 'class': 'hpt-badge hpt-warn' }, 'No text fields found. Paste the HTML of the form, including its inputs.'));
      return;
    }
    if (result.candidates.length === 0) {
      box.appendChild(el('p', {}, 'Found ' + result.total + ' text field' + (result.total === 1 ? '' : 's') + ', but none that looks like a hidden honeypot field.'));
      return;
    }

    var table = el('table', { 'class': 'hpt-table' });
    var head = el('thead');
    var headRow = el('tr');
    ['Field', 'Verdict', 'Details'].forEach(function (title) { headRow.appendChild(el('th', {}, title)); });
    head.appendChild(headRow);
    table.appendChild(head);
    var body = el('tbody');

    result.candidates.forEach(function (field) {
      var row = el('tr');
      var fieldCell = el('td');
      fieldCell.appendChild(el('span', { 'class': 'hpt-code' }, 'name="' + field.name + '"'));
      if (field.label) { fieldCell.appendChild(el('div', { 'class': 'hpt-muted' }, 'Label "' + field.label + '"')); }
      fieldCell.appendChild(el('div', { 'class': 'hpt-muted' }, 'Hidden by ' + field.hiddenBy));

      var verdictCell = el('td');
      verdictCell.appendChild(field.problems.length
        ? el('span', { 'class': 'hpt-badge hpt-bad' }, 'Likely filled by autofill')
        : field.warnings.length
          ? el('span', { 'class': 'hpt-badge hpt-warn' }, 'Could be safer')
          : el('span', { 'class': 'hpt-badge hpt-ok' }, 'Looks safe'));

      var detailCell = el('td');
      var list = el('ul', { 'class': 'hpt-list' });
      field.problems.concat(field.warnings).forEach(function (text) { list.appendChild(el('li', {}, text)); });
      if (list.children.length) { detailCell.appendChild(list); }

      row.appendChild(fieldCell);
      row.appendChild(verdictCell);
      row.appendChild(detailCell);
      body.appendChild(row);
    });

    table.appendChild(body);
    var scroll = el('div', { 'class': 'hpt-scroll' });
    scroll.appendChild(table);
    box.appendChild(scroll);
  }

  /* The page is compressed to one line, which collapses spaces inside strings, so indentation is generated. */
  function indent(size) { return new Array(size + 1).join(' '); }

  var EXAMPLE = [
    '<form method="POST" action="/contact">',
    indent(2) + '<label for="name">Name</label>',
    indent(2) + '<input id="name" name="name" type="text">',
    '',
    indent(2) + '<div style="display:none">',
    indent(4) + '<label for="website">Website</label>',
    indent(4) + '<input id="website" name="website" type="text">',
    indent(2) + '</div>',
    '',
    indent(2) + '<div class="extra" aria-hidden="true" style="position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)">',
    indent(4) + '<input name="referral_3f9a" type="text" tabindex="-1" autocomplete="off"',
    indent(11) + 'data-1p-ignore data-lpignore="true" data-bwignore data-form-type="other">',
    indent(2) + '</div>',
    '',
    indent(2) + '<textarea name="message"></textarea>',
    indent(2) + '<button type="submit">Send</button>',
    '</form>'
  ].join('\n');

  document.getElementById('hpt-example').addEventListener('click', function () {
    document.getElementById('hpt-html').value = EXAMPLE;
    renderCheck(checkForm(EXAMPLE));
  });
  document.getElementById('hpt-check').addEventListener('click', function () {
    renderCheck(checkForm(document.getElementById('hpt-html').value));
  });

  window.honeypotChecker = { checkForm: checkForm };
})();
</script>
