ALTER TABLE menu_items
ADD roast_level VARCHAR(20),          -- Light, Medium, Dark
ADD caffeine_level VARCHAR(20),       -- Low, Medium, High
ADD flavor_profile VARCHAR(100),      -- Nutty, Fruity, Chocolatey, etc.
ADD milk_based BOOLEAN,               -- TRUE if it contains milk (e.g., latte)
ADD iced_option BOOLEAN,              -- TRUE if can be served cold
ADD origin VARCHAR(50)