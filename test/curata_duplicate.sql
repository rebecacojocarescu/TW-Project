-- Script pentru identificarea și curățarea duplicatelor

-- Mai întâi să vedem ce duplicate avem
SELECT name, species, breed, COUNT(*) as numar_aparitii
FROM pets
GROUP BY name, species, breed
HAVING COUNT(*) > 1;

-- Să vedem detaliile complete pentru fiecare duplicat
SELECT id, name, species, breed, personality_description, activity_description, diet_description
FROM pets p
WHERE EXISTS (
    SELECT 1 
    FROM pets p2 
    WHERE p2.name = p.name 
    AND p2.species = p.species 
    AND p2.breed = p.breed
    GROUP BY p2.name, p2.species, p2.breed
    HAVING COUNT(*) > 1
)
ORDER BY name, species, breed, id;

-- Creăm o tabela temporară pentru a păstra ID-urile pe care vrem să le ștergem
CREATE GLOBAL TEMPORARY TABLE temp_delete_ids (
    id NUMBER
) ON COMMIT PRESERVE ROWS;

-- Inserăm în tabela temporară ID-urile înregistrărilor duplicate care au mai puține informații
INSERT INTO temp_delete_ids
SELECT p1.id
FROM pets p1
JOIN pets p2 ON p1.name = p2.name 
    AND p1.species = p2.species 
    AND p1.breed = p2.breed
    AND p1.id < p2.id
WHERE (p1.personality_description IS NULL AND p2.personality_description IS NOT NULL)
   OR (p1.activity_description IS NULL AND p2.activity_description IS NOT NULL)
   OR (p1.diet_description IS NULL AND p2.diet_description IS NOT NULL);

-- Afișăm ID-urile care vor fi șterse pentru verificare
SELECT * FROM temp_delete_ids;

-- IMPORTANT: Verifică rezultatele de mai sus înainte de a executa următoarea comandă!
-- Decomentează următoarea linie doar după ce ai verificat că ID-urile sunt corecte
-- DELETE FROM pets WHERE id IN (SELECT id FROM temp_delete_ids);

-- Curăță tabela temporară
DROP TABLE temp_delete_ids;

-- Verifică rezultatele finale
SELECT id, name, species, breed, personality_description, activity_description, diet_description
FROM pets
ORDER BY name, species, breed; 