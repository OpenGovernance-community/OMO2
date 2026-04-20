WITH RECURSIVE ParentTree AS (
    -- Étape 1 : Récupérer le noeud de départ
    SELECT 
        h.id AS IDholon,
        h.name AS holon_name,
        h.IDholon_template
    FROM holon h
    WHERE h.id = 13 -- ID du noeud sélectionné

    UNION ALL

    -- Étape 2 : Ajouter les parents récursivement
    SELECT 
        h.id AS IDholon,
        h.name AS holon_name,
        h.IDholon_template
    FROM holon h
    INNER JOIN ParentTree pt ON h.id = pt.IDholon_template
)

-- Étape 3 : Récupérer uniquement les propriétés et valeurs associées aux noeuds pertinents
SELECT 
    -- Identifiants
--    CASE 
--        WHEN pt.IDholon = 13 THEN MAX(hp.id) -- IDholonproperty pour le noeud demandé
--        ELSE NULL -- NULL pour les parents
--    END AS IDholonproperty,
   -- Valeur associée au noeud demandé (13)
    MAX(CASE WHEN pt.IDholon = 13 THEN hp.id ELSE NULL END) AS IDholonproperty,

    p.id AS IDproperty,            -- ID de la propriété
    p.shortname,                   -- Nom court de la propriété
    p.name,                        -- Nom complet de la propriété

    -- Valeur associée au noeud demandé (13)
    MAX(CASE WHEN pt.IDholon = 13 THEN hp.value ELSE NULL END) AS value,

    -- Valeurs concaténées des parents (dans l'ordre racine -> feuille)
--    GROUP_CONCAT(CASE WHEN hp.value IS NOT NULL THEN hp.value ELSE '' END ORDER BY pt.IDholon ASC SEPARATOR ', ') AS value_parents,
    GROUP_CONCAT(CASE WHEN pt.IDholon=13 THEN null ELSE hp.value END ORDER BY pt.IDholon ASC SEPARATOR '|') AS value_parents,

    -- Liste des ID des parents dans l'ordre racine -> feuille
    GROUP_CONCAT(CASE WHEN pt.IDholon=13 THEN null ELSE pt.IDholon END ORDER BY pt.IDholon ASC SEPARATOR ',') AS list_parent

FROM ParentTree pt

-- Jointure avec holon_property pour récupérer les valeurs des propriétés
LEFT JOIN holon_property hp ON hp.IDholon = pt.IDholon

-- Filtrer uniquement les propriétés associées à l'arborescence
INNER JOIN property p ON p.id = hp.IDproperty -- Inclut uniquement les propriétés liées

GROUP BY p.id
ORDER BY p.position
