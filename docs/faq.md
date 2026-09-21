---
title: "FAQ"
nav_order: 12
description: "Short answers about darvis/livewire-honeypot: what it is, which versions it supports, what it needs, how it compares to a CAPTCHA and what it does not stop."
faq: true
---

# Frequently asked questions

{% for item in site.data.faq %}
## {{ item.q }}

{{ item.a | markdownify }}
{% endfor %}
